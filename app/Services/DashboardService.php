<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Departure;
use App\Models\Group;
use App\Models\Muthawwif;
use App\Models\Pilgrim;
use App\Models\PilgrimLocation;
use App\Models\SosReport;
use App\Models\TourLeader;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $branchId = $user->hasRole(UserRole::BranchAdmin->value)
            ? $user->branch_id
            : null;

        return [
            'scopeLabel' => $branchId ? $user->branch?->name ?? 'Cabang' : 'Nasional',
            'cards' => $this->cards($branchId),
            'chart' => $this->chart($branchId),
            'monitoring' => $this->monitoring($branchId),
            'recentSos' => $this->recentSos($branchId),
            'upcomingDepartures' => $this->upcomingDepartures($branchId),
        ];
    }

    /**
     * @return list<array{label: string, value: int, icon: string, color: string}>
     */
    private function cards(?int $branchId): array
    {
        $scopedCount = static fn (string $model): int => $model::query()
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->count();

        $operationalCards = [
            ['label' => 'Total Jamaah', 'value' => $scopedCount(Pilgrim::class), 'icon' => 'users', 'color' => 'blue'],
            ['label' => 'Tour Leader', 'value' => $scopedCount(TourLeader::class), 'icon' => 'user-round-check', 'color' => 'emerald'],
            ['label' => 'Muthawwif', 'value' => $scopedCount(Muthawwif::class), 'icon' => 'book-open', 'color' => 'violet'],
            ['label' => 'Total Rombongan', 'value' => $scopedCount(Group::class), 'icon' => 'users-round', 'color' => 'amber'],
            ['label' => 'Total SOS', 'value' => $scopedCount(SosReport::class), 'icon' => 'siren', 'color' => 'red'],
        ];

        if ($branchId) {
            return $operationalCards;
        }

        return [
            ['label' => 'Total Cabang', 'value' => Branch::count(), 'icon' => 'building-2', 'color' => 'blue'],
            [
                'label' => 'Admin Cabang',
                'value' => User::role(UserRole::BranchAdmin->value)->count(),
                'icon' => 'shield-check',
                'color' => 'cyan',
            ],
            ...$operationalCards,
            ['label' => 'Keberangkatan', 'value' => Departure::count(), 'icon' => 'plane', 'color' => 'indigo'],
        ];
    }

    /**
     * @return array{labels: list<string>, pilgrims: list<int>, departures: list<int>, sos: list<int>}
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
            'departures' => $months->map(fn (CarbonImmutable $month) => Departure::query()
                ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
                ->whereBetween('departure_date', [$month->toDateString(), $month->endOfMonth()->toDateString()])
                ->count())->all(),
            'sos' => $months->map(fn (CarbonImmutable $month) => SosReport::query()
                ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
                ->whereBetween('reported_at', [$month, $month->endOfMonth()])
                ->count())->all(),
        ];
    }

    /**
     * @return array{online: int, offline: int, unknown: int, active_sos: int}
     */
    private function monitoring(?int $branchId): array
    {
        $locations = PilgrimLocation::query()
            ->when($branchId, fn (Builder $query) => $query->whereHas(
                'pilgrim',
                fn (Builder $pilgrimQuery) => $pilgrimQuery->where('branch_id', $branchId),
            ));

        return [
            'online' => (clone $locations)->where('gps_status', 'online')->count(),
            'offline' => (clone $locations)->where('gps_status', 'offline')->count(),
            'unknown' => (clone $locations)->where('gps_status', 'unknown')->count(),
            'active_sos' => SosReport::query()
                ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
                ->whereIn('status', ['active', 'acknowledged'])
                ->count(),
        ];
    }

    private function recentSos(?int $branchId)
    {
        return SosReport::query()
            ->with(['pilgrim:id,full_name,registration_number', 'branch:id,name'])
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->latest('reported_at')
            ->limit(5)
            ->get();
    }

    private function upcomingDepartures(?int $branchId)
    {
        return Departure::query()
            ->with('branch:id,name')
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->whereDate('departure_date', '>=', today())
            ->whereIn('status', ['draft', 'scheduled'])
            ->orderBy('departure_date')
            ->limit(5)
            ->get();
    }
}
