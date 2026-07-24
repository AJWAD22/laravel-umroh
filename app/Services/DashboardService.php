<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Departure;
use App\Models\Group;
use App\Models\AuditLog;
use App\Models\Pilgrim;
use App\Models\PilgrimLocation;
use App\Models\PilgrimRegistration;
use App\Models\SosReport;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class DashboardService
{
    public function __construct(private readonly SystemSettingService $settings) {}

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $branchId = $user->hasRole(UserRole::BranchAdmin->value)
            ? $user->branch_id
            : null;

        $monitoring = $this->monitoring($branchId);

        return [
            'isNational' => $branchId === null,
            'scopeLabel' => $branchId ? $user->branch?->name ?? 'Cabang' : 'Nasional',
            'cards' => $this->cards($branchId, $monitoring),
            'chart' => $this->chart($branchId),
            'monitoring' => $monitoring,
            'priorities' => $this->priorities($branchId),
            // Super Admin hanya menerima angka agregat. Detail individu dan
            // tindak lanjut SOS tetap menjadi tanggung jawab Admin Cabang.
            'recentSos' => $branchId
                ? SosReport::query()
                    ->with(['pilgrim:id,full_name', 'branch:id,name'])
                    ->active()
                    ->where('branch_id', $branchId)
                    ->latest('reported_at')
                    ->limit(5)
                    ->get()
                : collect(),
        ];
    }

    /**
     * @return list<array{label: string, value: int, icon: string, color: string}>
     */
    private function cards(?int $branchId, array $monitoring): array
    {
        $scopedCount = static fn (string $model): int => $model::query()
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->count();

        if ($branchId) {
            return [
                ['label' => 'Total Jamaah', 'value' => $scopedCount(Pilgrim::class), 'icon' => 'users', 'color' => 'blue'],
                ['label' => 'Pendaftaran Baru', 'value' => PilgrimRegistration::where('branch_id', $branchId)->where('status', 'submitted')->count(), 'icon' => 'clipboard-list', 'color' => 'cyan'],
                ['label' => 'Menunggu Pembayaran', 'value' => PilgrimRegistration::where('branch_id', $branchId)->where('payment_status', 'pending_branch_payment')->where('status', '!=', 'cancelled')->count(), 'icon' => 'wallet', 'color' => 'amber'],
                ['label' => 'Perjalanan Aktif', 'value' => Departure::where('branch_id', $branchId)->whereIn('status', ['scheduled', 'departed'])->count(), 'icon' => 'plane', 'color' => 'violet'],
                ['label' => 'SOS Aktif', 'value' => SosReport::active()->where('branch_id', $branchId)->count(), 'icon' => 'siren', 'color' => 'red'],
                ['label' => 'GPS Perlu Diperiksa', 'value' => $monitoring['offline'], 'icon' => 'triangle-alert', 'color' => 'indigo'],
            ];
        }

        return [
            ['label' => 'Cabang Aktif', 'value' => Branch::where('is_active', true)->count(), 'icon' => 'building-2', 'color' => 'blue'],
            [
                'label' => 'Admin Cabang',
                'value' => User::role(UserRole::BranchAdmin->value)->count(),
                'icon' => 'shield-check',
                'color' => 'cyan',
            ],
            ['label' => 'Total Jamaah', 'value' => $scopedCount(Pilgrim::class), 'icon' => 'users', 'color' => 'emerald'],
            ['label' => 'Perjalanan Aktif', 'value' => Departure::whereIn('status', ['scheduled', 'departed'])->count(), 'icon' => 'plane', 'color' => 'violet'],
            ['label' => 'SOS Nasional', 'value' => SosReport::active()->count(), 'icon' => 'siren', 'color' => 'red'],
            ['label' => 'GPS Perlu Diperiksa', 'value' => $monitoring['offline'], 'icon' => 'triangle-alert', 'color' => 'indigo'],
        ];
    }

    /** @return array<string, int> */
    private function priorities(?int $branchId): array
    {
        return [
            'branches' => Branch::query()->where('is_active', true)->count(),
            'branchAdmins' => User::role(UserRole::BranchAdmin->value)->count(),
            'registrations' => PilgrimRegistration::query()
                ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
                ->where('status', 'submitted')->count(),
            'payments' => PilgrimRegistration::query()
                ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
                ->where('payment_status', 'pending_branch_payment')
                ->where('status', '!=', 'cancelled')->count(),
            'sos' => SosReport::query()->active()
                ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))->count(),
            'departures' => Departure::query()
                ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
                ->whereIn('status', ['scheduled', 'departed'])->count(),
            'auditLogs' => AuditLog::query()
                ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
                ->count(),
        ];
    }

    /**
     * @return array{labels: list<string>, pilgrims: list<int>, groups: list<int>}
     */
    private function chart(?int $branchId): array
    {
        $months = collect(range(5, 0))
            ->map(fn (int $monthsAgo) => CarbonImmutable::now()->startOfMonth()->subMonths($monthsAgo));

        return [
            'labels' => $months->map(fn (CarbonImmutable $month) => $month->format('M Y'))->all(),
            'pilgrims' => $months->map(fn (CarbonImmutable $month) => Pilgrim::query()
                ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
                ->whereBetween('created_at', [$month, $month->endOfMonth()])
                ->count())->all(),
            'groups' => $months->map(fn (CarbonImmutable $month) => Group::query()
                ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
                ->whereBetween('created_at', [$month, $month->endOfMonth()])
                ->count())->all(),
        ];
    }

    /**
     * @return array{online: int, offline: int, unknown: int}
     */
    private function monitoring(?int $branchId): array
    {
        $locations = PilgrimLocation::query()
            ->when($branchId, fn (Builder $query) => $query->whereHas(
                'pilgrim',
                fn (Builder $pilgrimQuery) => $pilgrimQuery->where('branch_id', $branchId),
            ))
            ->whereHas(
                'pilgrim.user.mobileDevices',
                fn (Builder $deviceQuery) => $deviceQuery->whereNull('revoked_at'),
            );

        // Harus sama dengan Live Map supaya angka Dashboard tidak beda.
        // Batas ini bisa diubah dari Pengaturan Sistem.
        $offlineThreshold = now()->subMinutes(
            (int) $this->settings->get('gps_offline_threshold_minutes', 10)
        );

        return [
            'online' => (clone $locations)
                ->where('gps_status', 'online')
                ->where('recorded_at', '>=', $offlineThreshold)
                ->count(),
            'offline' => (clone $locations)
                ->where(function (Builder $query) use ($offlineThreshold): void {
                    $query->where('gps_status', 'offline')
                        ->orWhere(function (Builder $onlineQuery) use ($offlineThreshold): void {
                            $onlineQuery->where('gps_status', 'online')
                                ->where('recorded_at', '<', $offlineThreshold);
                        });
                })
                ->count(),
            'unknown' => (clone $locations)->where('gps_status', 'unknown')->count(),
        ];
    }
}
