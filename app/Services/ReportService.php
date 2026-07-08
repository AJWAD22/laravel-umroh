<?php

namespace App\Services;

use App\Models\LocationHistory;
use App\Models\Pilgrim;
use App\Models\SosReport;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class ReportService
{
    /**
     * @return array{title: string, headings: array<int, string>, rows: \Illuminate\Support\Collection, filters: array}
     */
    public function generate(string $type, array $filters): array
    {
        $from = CarbonImmutable::parse($filters['date_from'])->startOfDay();
        $to = CarbonImmutable::parse($filters['date_to'])->endOfDay();
        $branchId = $filters['branch_id'] ?? null;
        $status = $filters['status'] ?? null;

        return match ($type) {
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
                'headings' => ['Waktu', 'No. Registrasi', 'Nama Jamaah', 'Cabang', 'Koordinat', 'Status', 'Ditangani Oleh', 'Selesai'],
                'rows' => SosReport::query()->with(['pilgrim:id,registration_number,full_name', 'branch:id,name', 'handler:id,name'])
                    ->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
                    ->when($status, fn (Builder $q) => $q->where('status', $status))
                    ->whereBetween('reported_at', [$from, $to])->orderBy('reported_at')->get()
                    ->map(fn (SosReport $item) => [
                        $item->reported_at?->format('d-m-Y H:i:s') ?? '-',
                        $item->pilgrim?->registration_number ?? '-',
                        $item->pilgrim?->full_name ?? 'Jamaah tidak ditemukan',
                        $item->branch?->name ?? '-',
                        "{$item->latitude}, {$item->longitude}",
                        $item->status, $item->handler?->name ?? '-', $item->resolved_at?->format('d-m-Y H:i:s') ?? '-',
                    ]),
                'filters' => $filters,
            ],
            default => abort(404),
        };
    }
}
