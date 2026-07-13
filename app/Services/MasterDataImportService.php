<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Imports\SpreadsheetArrayImport;
use App\Models\Branch;
use App\Models\Group;
use App\Models\Muthawwif;
use App\Models\Pilgrim;
use App\Models\TourLeader;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class MasterDataImportService
{
    public function __construct(
        private readonly MasterDataService $masterData,
        private readonly MobileActivationService $activations,
    ) {}

    public function headings(string $resource): array
    {
        return match ($resource) {
            'branches' => ['kode_cabang', 'nama_cabang', 'kota', 'provinsi', 'telepon', 'email', 'alamat', 'aktif'],
            'branch-admins' => ['cabang', 'nama', 'email', 'telepon', 'password', 'aktif'],
            'pilgrims' => ['cabang', 'rombongan', 'nama', 'nik', 'nomor_paspor', 'masa_berlaku_paspor', 'jenis_kelamin', 'telepon', 'tanggal_lahir', 'alamat', 'status'],
            'tour-leaders' => ['cabang', 'nama', 'email_login', 'telepon', 'password', 'aktif'],
            'muthawwifs' => ['cabang', 'nama', 'email_login', 'telepon', 'bahasa', 'password', 'aktif'],
            'groups' => ['cabang', 'nama_rombongan', 'tour_leader', 'muthawwif', 'kapasitas', 'catatan', 'aktif'],
            default => abort(404),
        };
    }

    public function exampleRows(string $resource): array
    {
        return match ($resource) {
            'branches' => [['BJM', 'Cabang Banjarmasin', 'Banjarmasin', 'Kalimantan Selatan', '081234567890', 'banjarmasin@mantauumroh.id', 'Jl. Ahmad Yani No. 10', 'ya']],
            'branch-admins' => [['BJM', 'Admin Banjarmasin', 'admin.banjarmasin@mantauumroh.id', '081234567891', 'password123', 'ya']],
            'pilgrims' => [['BJM', 'Rombongan Al-Ikhlas', 'Ahmad Fauzi', '6371010101700001', 'A1234567', '2030-12-31', 'laki-laki', '081234567892', '1970-01-01', 'Banjarmasin', 'registered']],
            'tour-leaders' => [['BJM', 'Fajar Rahman', 'fajar@mantauumroh.id', '081234567893', 'password123', 'ya']],
            'muthawwifs' => [['BJM', 'Ustadz Khalid', 'khalid@mantauumroh.id', '081234567894', 'Indonesia, Arab', 'password123', 'ya']],
            'groups' => [['BJM', 'Rombongan Al-Ikhlas', 'Fajar Rahman', 'Ustadz Khalid', '45', 'Keberangkatan reguler', 'ya']],
            default => [],
        };
    }

    public function import(string $resource, UploadedFile $file, User $actor): array
    {
        $sheets = Excel::toArray(new SpreadsheetArrayImport, $file);
        $sheet = $sheets[0] ?? [];

        if (count($sheet) < 2) {
            throw ValidationException::withMessages([
                'file' => 'File Excel masih kosong. Gunakan template dan isi minimal satu baris data.',
            ]);
        }

        $headers = array_map(fn ($value) => $this->normalizeKey((string) $value), array_shift($sheet));
        $rows = $this->rowsFromSheet($headers, $sheet);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'Tidak ada baris data yang bisa diimport.',
            ]);
        }

        $result = [
            'created' => 0,
            'updated' => 0,
            'pins' => 0,
        ];

        DB::transaction(function () use ($resource, $rows, $actor, &$result): void {
            foreach ($rows as $rowNumber => $row) {
                try {
                    [$data, $record] = $this->mapRow($resource, $row, $actor);
                    $wasExisting = $record instanceof Model;
                    if ($wasExisting && method_exists($record, 'trashed') && $record->trashed()) {
                        $record->restore();
                    }
                    $model = $this->masterData->save($resource, $data, $actor, $record);

                    if ($resource === 'pilgrims' && $this->shouldGeneratePin($model, $wasExisting)) {
                        $this->activations->generatePin($actor, $model);
                        $result['pins']++;
                    }

                    $result[$wasExisting ? 'updated' : 'created']++;
                } catch (ValidationException $exception) {
                    throw $exception;
                } catch (\Throwable $exception) {
                    throw ValidationException::withMessages([
                        'file' => "Baris {$rowNumber}: {$exception->getMessage()}",
                    ]);
                }
            }
        });

        return $result;
    }

    private function rowsFromSheet(array $headers, array $sheet): array
    {
        $rows = [];

        foreach ($sheet as $index => $values) {
            if ($this->isEmptyRow($values)) {
                continue;
            }

            $row = [];
            foreach ($headers as $column => $header) {
                if ($header === '') {
                    continue;
                }
                $row[$header] = $values[$column] ?? null;
            }

            $rows[$index + 2] = $row;
        }

        return $rows;
    }

    private function mapRow(string $resource, array $row, User $actor): array
    {
        return match ($resource) {
            'branches' => $this->mapBranch($row),
            'branch-admins' => $this->mapBranchAdmin($row, $actor),
            'pilgrims' => $this->mapPilgrim($row, $actor),
            'tour-leaders' => $this->mapTourLeader($row, $actor),
            'muthawwifs' => $this->mapMuthawwif($row, $actor),
            'groups' => $this->mapGroup($row, $actor),
            default => abort(404),
        };
    }

    private function mapBranch(array $row): array
    {
        $code = Str::upper($this->required($row, 'kode_cabang', 'kode_cabang'));

        return [
            [
                'code' => $code,
                'name' => $this->required($row, 'nama_cabang', 'nama_cabang'),
                'city' => $this->required($row, 'kota', 'kota'),
                'province' => $this->value($row, 'provinsi'),
                'phone' => $this->value($row, 'telepon'),
                'email' => $this->value($row, 'email'),
                'address' => $this->value($row, 'alamat'),
                'is_active' => $this->bool($this->value($row, 'aktif'), true),
            ],
            Branch::withTrashed()->where('code', $code)->first(),
        ];
    }

    private function mapBranchAdmin(array $row, User $actor): array
    {
        $email = Str::lower($this->required($row, 'email', 'email'));
        $record = User::role(UserRole::BranchAdmin->value)->where('email', $email)->first();

        return [
            [
                'branch_id' => $this->branch($row, $actor)->id,
                'name' => $this->required($row, 'nama', 'nama'),
                'email' => $email,
                'phone_number' => $this->value($row, 'telepon'),
                'password' => $this->value($row, 'password') ?: ($record ? null : 'password123'),
                'is_active' => $this->bool($this->value($row, 'aktif'), true),
            ],
            $record,
        ];
    }

    private function mapPilgrim(array $row, User $actor): array
    {
        $branch = $this->branch($row, $actor);
        $nik = $this->value($row, 'nik');
        $passport = $this->value($row, 'nomor_paspor');
        $record = null;

        if ($nik) {
            $record = Pilgrim::withTrashed()->where('nik', $nik)->first();
        }
        if (! $record && $passport) {
            $record = Pilgrim::withTrashed()->where('passport_number', $passport)->first();
        }

        return [
            [
                'branch_id' => $branch->id,
                'group_id' => $this->optionalGroup($row, $branch)?->id,
                'full_name' => $this->required($row, 'nama', 'nama'),
                'nik' => $nik,
                'passport_number' => $passport,
                'passport_expired_at' => $this->date($this->value($row, 'masa_berlaku_paspor')),
                'gender' => $this->gender($this->required($row, 'jenis_kelamin', 'jenis_kelamin')),
                'phone' => $this->value($row, 'telepon'),
                'birth_date' => $this->date($this->value($row, 'tanggal_lahir')),
                'address' => $this->value($row, 'alamat'),
                'status' => $this->value($row, 'status') ?: 'registered',
            ],
            $record,
        ];
    }

    private function mapTourLeader(array $row, User $actor): array
    {
        $email = Str::lower($this->required($row, 'email_login', 'email_login'));
        $record = TourLeader::whereHas('user', fn ($query) => $query->where('email', $email))->first();

        return [
            [
                'branch_id' => $this->branch($row, $actor)->id,
                'full_name' => $this->required($row, 'nama', 'nama'),
                'phone' => $this->value($row, 'telepon'),
                'email' => $email,
                'password' => $this->value($row, 'password') ?: ($record ? null : 'password123'),
                'is_active' => $this->bool($this->value($row, 'aktif'), true),
            ],
            $record,
        ];
    }

    private function mapMuthawwif(array $row, User $actor): array
    {
        $email = Str::lower($this->required($row, 'email_login', 'email_login'));
        $record = Muthawwif::whereHas('user', fn ($query) => $query->where('email', $email))->first();

        return [
            [
                'branch_id' => $this->branch($row, $actor)->id,
                'full_name' => $this->required($row, 'nama', 'nama'),
                'phone' => $this->value($row, 'telepon'),
                'languages' => $this->value($row, 'bahasa'),
                'email' => $email,
                'password' => $this->value($row, 'password') ?: ($record ? null : 'password123'),
                'is_active' => $this->bool($this->value($row, 'aktif'), true),
            ],
            $record,
        ];
    }

    private function mapGroup(array $row, User $actor): array
    {
        $branch = $this->branch($row, $actor);
        $name = $this->required($row, 'nama_rombongan', 'nama_rombongan');

        return [
            [
                'branch_id' => $branch->id,
                'name' => $name,
                'tour_leader_id' => $this->optionalTourLeader($row, $branch)?->id,
                'muthawwif_id' => $this->optionalMuthawwif($row, $branch)?->id,
                'capacity' => $this->integer($this->value($row, 'kapasitas')),
                'notes' => $this->value($row, 'catatan'),
                'is_active' => $this->bool($this->value($row, 'aktif'), true),
            ],
            Group::withTrashed()->where('branch_id', $branch->id)->where('name', $name)->first(),
        ];
    }

    private function branch(array $row, User $actor): Branch
    {
        if (! $actor->hasRole(UserRole::SuperAdmin->value)) {
            return Branch::findOrFail($actor->branch_id);
        }

        $value = $this->required($row, 'cabang', 'cabang');

        return Branch::withTrashed()
            ->where('code', $value)
            ->orWhere('name', $value)
            ->orWhere('city', $value)
            ->firstOr(fn () => throw new \RuntimeException("Cabang '{$value}' tidak ditemukan."));
    }

    private function optionalGroup(array $row, Branch $branch): ?Group
    {
        $value = $this->value($row, 'rombongan');
        if (! $value) {
            return null;
        }

        return Group::where('branch_id', $branch->id)
            ->where(fn ($query) => $query->where('code', $value)->orWhere('name', $value))
            ->firstOr(fn () => throw new \RuntimeException("Rombongan '{$value}' tidak ditemukan di cabang {$branch->name}."));
    }

    private function optionalTourLeader(array $row, Branch $branch): ?TourLeader
    {
        $value = $this->value($row, 'tour_leader');
        if (! $value) {
            return null;
        }

        return TourLeader::where('branch_id', $branch->id)
            ->where(function ($query) use ($value): void {
                $query->where('employee_number', $value)
                    ->orWhere('full_name', $value)
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('email', $value));
            })
            ->firstOr(fn () => throw new \RuntimeException("Tour Leader '{$value}' tidak ditemukan di cabang {$branch->name}."));
    }

    private function optionalMuthawwif(array $row, Branch $branch): ?Muthawwif
    {
        $value = $this->value($row, 'muthawwif');
        if (! $value) {
            return null;
        }

        return Muthawwif::where('branch_id', $branch->id)
            ->where(function ($query) use ($value): void {
                $query->where('employee_number', $value)
                    ->orWhere('full_name', $value)
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('email', $value));
            })
            ->firstOr(fn () => throw new \RuntimeException("Muthawwif '{$value}' tidak ditemukan di cabang {$branch->name}."));
    }

    private function shouldGeneratePin(Model $model, bool $wasExisting): bool
    {
        return $model instanceof Pilgrim
            && (! $wasExisting || $model->activation_pin_generated_at === null)
            && $model->activation_pin_used_at === null;
    }

    private function required(array $row, string $key, string $label): string
    {
        $value = $this->value($row, $key);

        if (! filled($value)) {
            throw new \RuntimeException("Kolom {$label} wajib diisi.");
        }

        return $value;
    }

    private function value(array $row, string $key): ?string
    {
        $value = $row[$key] ?? null;

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function bool(?string $value, bool $default): bool
    {
        if ($value === null) {
            return $default;
        }

        return in_array(Str::lower($value), ['1', 'true', 'ya', 'yes', 'aktif', 'active'], true);
    }

    private function gender(string $value): string
    {
        $value = Str::lower($value);

        return match (true) {
            in_array($value, ['l', 'laki-laki', 'laki laki', 'pria', 'male'], true) => 'male',
            in_array($value, ['p', 'perempuan', 'wanita', 'female'], true) => 'female',
            default => throw new \RuntimeException('jenis_kelamin harus laki-laki atau perempuan.'),
        };
    }

    private function date(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (is_numeric($value)) {
            return Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            throw new \RuntimeException("Format tanggal '{$value}' tidak dikenali. Gunakan format YYYY-MM-DD.");
        }

        return date('Y-m-d', $timestamp);
    }

    private function integer(?string $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    private function normalizeKey(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replace([' ', '-', '.', '/', '\\'], '_')
            ->replaceMatches('/[^a-z0-9_]/', '')
            ->replaceMatches('/_+/', '_')
            ->trim('_')
            ->toString();
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (filled($value)) {
                return false;
            }
        }

        return true;
    }
}
