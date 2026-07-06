<?php

namespace App\Services;

use App\Models\SosReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SosResolutionService
{
    public function resolve(SosReport $report, User $handler, ?string $notes = null): SosReport
    {
        return DB::transaction(function () use ($report, $handler, $notes): SosReport {
            $report = SosReport::query()
                ->whereKey($report->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($report->status !== 'resolved') {
                $report->update([
                    'status' => 'resolved',
                    'handled_by' => $handler->id,
                    'acknowledged_at' => $report->acknowledged_at ?? now(),
                    'resolved_at' => now(),
                    'resolution_notes' => $notes,
                ]);

                $hasOtherActiveSos = SosReport::query()
                    ->where('pilgrim_id', $report->pilgrim_id)
                    ->whereKeyNot($report->id)
                    ->whereIn('status', ['active', 'acknowledged'])
                    ->exists();

                if (! $hasOtherActiveSos) {
                    $report->pilgrim()->update(['monitoring_status' => 'normal']);
                }
            }

            return $report->refresh();
        });
    }
}
