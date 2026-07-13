<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Imports\SpreadsheetArrayImport;
use App\Models\Branch;
use App\Models\Group;
use App\Models\Pilgrim;
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
        // Saat ini import Excel sengaja dibatasi hanya untuk Jamaah.
        // Data lain seperti cabang, admin cabang, TL, Muthawwif, dan rombongan
        // lebih aman dibuat lewat form agar relasi dan akun login tidak kacau.
        abort_unless($resource === 'pilgrims', 404);

        return [
            'cabang',
            'rombongan',
            'nama',
            'nik',
            'nomor_paspor',
            'masa_berlaku_paspor',
            'jenis_kelamin',
            'telepon',
            'tanggal_lahir',
            'alamat',
            'status',
        ];
    }

    public function exampleRows(string $resource): array
    {
        abort_unless($resource === 'pilgrims', 404);

        return [[
            'BJM',
            'Rombongan Al-Ikhlas',
            'Ahmad Fauzi',
            '6371010101700001',
            'A1234567',
            '2030-12-31',
            'laki-laki',
            '081234567892',
            '1970-01-01',
            'Banjarmasin',
            'registered',
        ]];
    }

    public function import(string $resource, UploadedFile $file, User $actor): array
    {
        abort_unless($resource === 'pilgrims', 404);

        // File Excel dibaca sebagai array biasa.
        // Baris pertama dianggap sebagai judul kolom, baris berikutnya adalah data Jamaah.
        $sheets = Excel::toArray(new SpreadsheetArrayImport, $file);
        $sheet = $sheets[0] ?? [];

        if (count($sheet) < 2) {
            throw ValidationException::withMessages([
                'file' => 'File Excel masih kosong. Gunakan template Jamaah dan isi minimal satu baris data.',
            ]);
        }

        $headers = array_map(fn ($value) => $this->normalizeKey((string) $value), array_shift($sheet));
        $rows = $this->rowsFromSheet($headers, $sheet);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'Tidak ada baris data jamaah yang bisa diimport.',
            ]);
        }

        $result = [
            'created' => 0,
            'updated' => 0,
            'pins' => 0,
        ];

        DB::transaction(function () use ($rows, $actor, &$result): void {
            foreach ($rows as $rowNumber => $row) {
                try {
                    // Setiap baris Excel diubah ke format yang dipakai form Jamaah.
                    // Jika NIK atau nomor paspor sudah ada, data akan diperbarui.
                    // Jika belum ada, sistem membuat Jamaah baru.
                    [$data, $record] = $this->mapPilgrim($row, $actor);
                    $wasExisting = $record instanceof Model;

                    if ($wasExisting && method_exists($record, 'trashed') && $record->trashed()) {
                        $record->restore();
                    }

                    $model = $this->masterData->save('pilgrims', $data, $actor, $record);

                    // PIN aktivasi dibuat otomatis hanya untuk Jamaah baru
                    // atau Jamaah lama yang belum pernah punya PIN.
                    if ($this->shouldGeneratePin($model, $wasExisting)) {
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

    private function mapPilgrim(array $row, User $actor): array
    {
        // Admin Cabang tidak perlu mengisi cabang di Excel.
        // Sistem otomatis memakai cabang milik akun admin yang sedang login.
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

    private function branch(array $row, User $actor): Branch
    {
        if (! $actor->hasRole(UserRole::SuperAdmin->value)) {
            return Branch::findOrFail($actor->branch_id);
        }

        // Super Admin bisa import untuk cabang mana saja.
        // Isi kolom cabang dengan kode cabang, nama cabang, atau kota.
        $value = $this->required($row, 'cabang', 'cabang');

        return Branch::withTrashed()
            ->where('code', $value)
            ->orWhere('name', $value)
            ->orWhere('city', $value)
            ->firstOr(fn () => throw new \RuntimeException("Cabang '{$value}' tidak ditemukan."));
    }

    private function optionalGroup(array $row, Branch $branch): ?Group
    {
        // Rombongan boleh dikosongkan.
        // Jika diisi, namanya harus sudah ada di cabang tersebut.
        $value = $this->value($row, 'rombongan');
        if (! $value) {
            return null;
        }

        return Group::where('branch_id', $branch->id)
            ->where(fn ($query) => $query->where('code', $value)->orWhere('name', $value))
            ->firstOr(fn () => throw new \RuntimeException("Rombongan '{$value}' tidak ditemukan di cabang {$branch->name}."));
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
