<?php

namespace App\Services;

use App\Models\LocationHistory;
use App\Models\Pilgrim;
use App\Models\SosReport;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

class ReportService
{
    /**
     * @return array{title: string, headings: array<int, string>, rows: \Illuminate\Support\Collection, filters: array}
     */
    public function generate(string $type, array $filters, bool $aggregateOnly = false): array
    {
        $from = CarbonImmutable::parse($filters['date_from'])->startOfDay();
        $to = CarbonImmutable::parse($filters['date_to'])->endOfDay();
        $branchId = $filters['branch_id'] ?? null;
        $status = $filters['status'] ?? null;

        if ($aggregateOnly) {
            return $this->aggregate($type, $filters, $from, $to, $branchId, $status);
        }

        return match ($type) {
            'all' => [
                'title' => 'Laporan Gabungan',
                'headings' => ['Kategori', 'Waktu', 'No. Registrasi', 'Nama Jamaah', 'Cabang', 'Rombongan', 'Status', 'Detail'],
                'rows' => collect()
                    ->concat(Pilgrim::query()->with('branch:id,name')
                        ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
                        ->whereBetween('created_at', [$from, $to])->orderBy('created_at')->get()
                        ->map(fn (Pilgrim $item) => [
                            'Jamaah',
                            $item->created_at->format('d-m-Y H:i:s'),
                            $item->registration_number,
                            $item->full_name,
                            $item->branch?->name ?? '-',
                            '-',
                            $item->status,
                            'Data jamaah dibuat/diperbarui',
                        ]))
                    ->concat(LocationHistory::query()->with('pilgrim.branch:id,name')
                        ->when($branchId, fn (Builder $q) => $q->whereHas('pilgrim', fn (Builder $p) => $p->where('branch_id', $branchId)))
                        ->whereBetween('recorded_at', [$from, $to])->orderBy('recorded_at')->get()
                        ->map(fn (LocationHistory $item) => [
                            'Tracking',
                            $item->recorded_at?->format('d-m-Y H:i:s') ?? '-',
                            $item->pilgrim?->registration_number ?? '-',
                            $item->pilgrim?->full_name ?? 'Jamaah tidak ditemukan',
                            $item->pilgrim?->branch?->name ?? '-',
                            '-',
                            'Terkirim',
                            "{$item->latitude}, {$item->longitude}",
                        ]))
                    ->concat(SosReport::query()->with(['pilgrim.branch:id,name', 'group:id,name'])
                        ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
                        ->whereBetween('reported_at', [$from, $to])->orderBy('reported_at')->get()
                        ->map(fn (SosReport $item) => [
                            'SOS',
                            $item->reported_at?->format('d-m-Y H:i:s') ?? '-',
                            $item->pilgrim?->registration_number ?? '-',
                            $item->pilgrim?->full_name ?? 'Jamaah tidak ditemukan',
                            $item->pilgrim?->branch?->name ?? '-',
                            $item->group?->name ?? '-',
                            $item->status,
                            trim(($item->message ?: 'Laporan darurat').' | '.$item->latitude.', '.$item->longitude),
                        ]))
                    ->sortBy(fn (array $row) => $row[1])
                    ->values(),
                'filters' => $filters,
            ],
            'pilgrims' => [
                'title' => 'Laporan Jamaah',
                'headings' => ['No. Registrasi', 'Nama', 'Cabang', 'Telepon', 'Gender', 'Status', 'Tanggal Daftar'],
                'rows' => Pilgrim::query()->with('branch:id,name')
                    ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
                    ->when($status, fn (Builder $q) => $q->where('status', $status))
                    ->whereBetween('created_at', [$from, $to])->orderBy('full_name')->get()
                    ->map(fn (Pilgrim $item) => [
                        $item->registration_number, $item->full_name, $item->branch?->name ?? '-',
                        $item->phone ?: '-', $item->gender, $item->status, $item->created_at->format('d-m-Y'),
                    ]),
                'filters' => $filters,
            ],
            'tracking' => [
                'title' => 'Laporan Tracking',
                'headings' => ['Waktu', 'No. Registrasi', 'Nama Jamaah', 'Cabang', 'Latitude', 'Longitude', 'Akurasi (m)', 'Battery'],
                'rows' => LocationHistory::query()->with('pilgrim.branch:id,name')
                    ->when($branchId, fn (Builder $q) => $q->whereHas('pilgrim', fn (Builder $p) => $p->where('branch_id', $branchId)))
                    ->whereBetween('recorded_at', [$from, $to])->orderBy('recorded_at')->get()
                    ->map(fn (LocationHistory $item) => [
                        $item->recorded_at?->format('d-m-Y H:i:s') ?? '-',
                        $item->pilgrim?->registration_number ?? '-',
                        $item->pilgrim?->full_name ?? 'Jamaah tidak ditemukan',
                        $item->pilgrim?->branch?->name ?? '-',
                        $item->latitude,
                        $item->longitude, $item->accuracy ?: '-', $item->battery_level ?? '-',
                    ]),
                'filters' => $filters,
            ],
            'sos' => [
                'title' => 'Laporan SOS',
                'headings' => ['Waktu SOS', 'No. Registrasi', 'Nama Jamaah', 'Cabang', 'Rombongan', 'Status', 'Ditangani Oleh', 'Koordinat', 'Catatan'],
                'rows' => SosReport::query()->with(['pilgrim.branch:id,name', 'group:id,name', 'handler:id,name'])
                    ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
                    ->when($status, fn (Builder $q) => $q->where('status', $status))
                    ->whereBetween('reported_at', [$from, $to])->orderBy('reported_at')->get()
                    ->map(fn (SosReport $item) => [
                        $item->reported_at?->format('d-m-Y H:i:s') ?? '-',
                        $item->pilgrim?->registration_number ?? '-',
                        $item->pilgrim?->full_name ?? 'Jamaah tidak ditemukan',
                        $item->pilgrim?->branch?->name ?? '-',
                        $item->group?->name ?? '-',
                        $item->status,
                        $item->handler?->name ?? '-',
                        "{$item->latitude}, {$item->longitude}",
                        $item->resolution_notes ?: $item->message ?: '-',
                    ]),
                'filters' => $filters,
            ],
            default => abort(404),
        };
    }

    /**
     * Super Admin hanya menerima laporan agregat. Ini menjaga rancangan hak
     * akses: tidak ada nama jamaah, nomor registrasi, koordinat, atau detail
     * SOS individual di laporan nasional.
     *
     * @return array{title: string, headings: array<int, string>, rows: Collection, filters: array}
     */
    private function aggregate(
        string $type,
        array $filters,
        CarbonImmutable $from,
        CarbonImmutable $to,
        mixed $branchId,
        mixed $status,
    ): array {
        $period = $from->format('d-m-Y').' s/d '.$to->format('d-m-Y');

        return match ($type) {
            'all' => [
                'title' => 'Laporan Gabungan Agregat',
                'headings' => ['Kategori', 'Cabang', 'Status', 'Jumlah', 'Periode'],
                'rows' => collect()
                    ->concat($this->aggregatePilgrims($from, $to, $branchId, $status)
                        ->map(fn (array $row) => ['Jamaah', $row['branch'], $row['status'], $row['count'], $period]))
                    ->concat($this->aggregateTracking($from, $to, $branchId)
                        ->map(fn (array $row) => ['Tracking', $row['branch'], 'Terkirim', $row['count'], $period]))
                    ->concat($this->aggregateSos($from, $to, $branchId, $status)
                        ->map(fn (array $row) => ['SOS', $row['branch'], $row['status'], $row['count'], $period]))
                    ->values(),
                'filters' => $filters,
            ],
            'pilgrims' => [
                'title' => 'Laporan Jamaah Agregat',
                'headings' => ['Cabang', 'Status', 'Jumlah', 'Periode'],
                'rows' => $this->aggregatePilgrims($from, $to, $branchId, $status)
                    ->map(fn (array $row) => [$row['branch'], $row['status'], $row['count'], $period])
                    ->values(),
                'filters' => $filters,
            ],
            'tracking' => [
                'title' => 'Laporan Tracking Agregat',
                'headings' => ['Cabang', 'Jumlah Titik Lokasi', 'Periode'],
                'rows' => $this->aggregateTracking($from, $to, $branchId)
                    ->map(fn (array $row) => [$row['branch'], $row['count'], $period])
                    ->values(),
                'filters' => $filters,
            ],
            'sos' => [
                'title' => 'Laporan SOS Agregat',
                'headings' => ['Cabang', 'Status', 'Jumlah', 'Periode'],
                'rows' => $this->aggregateSos($from, $to, $branchId, $status)
                    ->map(fn (array $row) => [$row['branch'], $row['status'], $row['count'], $period])
                    ->values(),
                'filters' => $filters,
            ],
            default => abort(404),
        };
    }

    private function aggregatePilgrims(CarbonImmutable $from, CarbonImmutable $to, mixed $branchId, mixed $status): Collection
    {
        return Pilgrim::query()
            ->with('branch:id,name')
            ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->when($status, fn (Builder $q) => $q->where('status', $status))
            ->whereBetween('created_at', [$from, $to])
            ->get()
            ->groupBy(fn (Pilgrim $item) => ($item->branch?->name ?? '-').'|'.$item->status)
            ->map(fn (Collection $items): array => [
                'branch' => $items->first()->branch?->name ?? '-',
                'status' => $items->first()->status,
                'count' => $items->count(),
            ])
            ->sortBy(['branch', 'status'])
            ->values();
    }

    private function aggregateTracking(CarbonImmutable $from, CarbonImmutable $to, mixed $branchId): Collection
    {
        return LocationHistory::query()
            ->with('pilgrim.branch:id,name')
            ->when($branchId, fn (Builder $q) => $q->whereHas('pilgrim', fn (Builder $p) => $p->where('branch_id', $branchId)))
            ->whereBetween('recorded_at', [$from, $to])
            ->get()
            ->groupBy(fn (LocationHistory $item) => $item->pilgrim?->branch?->name ?? '-')
            ->map(fn (Collection $items, string $branch): array => [
                'branch' => $branch,
                'count' => $items->count(),
            ])
            ->sortBy('branch')
            ->values();
    }

    private function aggregateSos(CarbonImmutable $from, CarbonImmutable $to, mixed $branchId, mixed $status): Collection
    {
        return SosReport::query()
            ->with('branch:id,name')
            ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->when($status, fn (Builder $q) => $q->where('status', $status))
            ->whereBetween('reported_at', [$from, $to])
            ->get()
            ->groupBy(fn (SosReport $item) => ($item->branch?->name ?? '-').'|'.$item->status)
            ->map(fn (Collection $items): array => [
                'branch' => $items->first()->branch?->name ?? '-',
                'status' => $items->first()->status,
                'count' => $items->count(),
            ])
            ->sortBy(['branch', 'status'])
            ->values();
    }
}
