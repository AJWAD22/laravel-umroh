<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Pilgrim;
use App\Models\SosReport;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SosReportManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_admin_only_sees_sos_reports_from_its_branch(): void
    {
        [$admin, $ownReport, $foreignReport] = $this->scenario();

        $this->actingAs($admin)
            ->get(route('monitoring.sos.index'))
            ->assertOk()
            ->assertSee($ownReport->pilgrim->full_name)
            ->assertDontSee($foreignReport->pilgrim->full_name);
    }

    public function test_branch_admin_can_resolve_sos_and_audit_handler_is_recorded(): void
    {
        [$admin, $report] = $this->scenario();

        $this->actingAs($admin)
            ->patch(route('monitoring.sos.resolve', $report), [
                'resolution_notes' => 'Jamaah sudah dijemput Tour Leader.',
            ])
            ->assertRedirect(route('monitoring.sos.show', $report))
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertSame('resolved', $report->status);
        $this->assertSame($admin->id, $report->handled_by);
        $this->assertNotNull($report->acknowledged_at);
        $this->assertNotNull($report->resolved_at);
        $this->assertSame('Jamaah sudah dijemput Tour Leader.', $report->resolution_notes);
    }

    public function test_branch_admin_cannot_view_or_resolve_another_branch_report(): void
    {
        [$admin, , $foreignReport] = $this->scenario();

        $this->actingAs($admin)
            ->get(route('monitoring.sos.show', $foreignReport))
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('monitoring.sos.resolve', $foreignReport))
            ->assertForbidden();

        $this->assertSame('active', $foreignReport->fresh()->status);
    }

    /**
     * @return array{User, SosReport, SosReport}
     */
    private function scenario(): array
    {
        $this->seed(RolePermissionSeeder::class);
        $branchA = Branch::create(['code' => 'SOS-A', 'name' => 'Cabang SOS A', 'city' => 'Makassar']);
        $branchB = Branch::create(['code' => 'SOS-B', 'name' => 'Cabang SOS B', 'city' => 'Jakarta']);
        $admin = User::factory()->create(['branch_id' => $branchA->id]);
        $admin->assignRole(UserRole::BranchAdmin->value);

        $pilgrimA = $this->pilgrim($branchA, 'SOS-JMH-A', 'Jamaah SOS Cabang A');
        $pilgrimB = $this->pilgrim($branchB, 'SOS-JMH-B', 'Jamaah Rahasia Cabang B');

        return [
            $admin,
            $this->report($branchA, $pilgrimA),
            $this->report($branchB, $pilgrimB),
        ];
    }

    private function pilgrim(Branch $branch, string $number, string $name): Pilgrim
    {
        return Pilgrim::create([
            'branch_id' => $branch->id,
            'registration_number' => $number,
            'full_name' => $name,
            'gender' => 'male',
            'status' => 'active',
        ]);
    }

    private function report(Branch $branch, Pilgrim $pilgrim): SosReport
    {
        return SosReport::create([
            'branch_id' => $branch->id,
            'pilgrim_id' => $pilgrim->id,
            'latitude' => 21.4224870,
            'longitude' => 39.8262060,
            'message' => 'Butuh bantuan segera.',
            'status' => 'active',
            'reported_at' => now(),
        ]);
    }
}
