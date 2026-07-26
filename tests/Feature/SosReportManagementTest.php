<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Group;
use App\Models\Pilgrim;
use App\Models\SosReport;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SosReportManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_admin_only_sees_sos_reports_from_their_branch(): void
    {
        [$admin, $report] = $this->scenario();
        $foreignReport = $this->reportForBranch('SOS-FGN', 'Cabang Lain');

        $this->actingAs($admin)
            ->get(route('monitoring.sos.index'))
            ->assertOk()
            ->assertSee($report->pilgrim->full_name)
            ->assertDontSee($foreignReport->pilgrim->full_name)
            ->assertSee('Pusat Respons SOS')
            ->assertSee('Tutup dengan catatan');
    }

    public function test_branch_admin_cannot_open_foreign_sos_report(): void
    {
        [$admin] = $this->scenario();
        $foreignReport = $this->reportForBranch('SOS-FGN', 'Cabang Lain');

        $this->actingAs($admin)
            ->get(route('monitoring.sos.show', $foreignReport))
            ->assertForbidden();
    }

    public function test_resolution_notes_are_required_when_closing_sos(): void
    {
        [$admin, $report] = $this->scenario();

        $this->actingAs($admin)
            ->from(route('monitoring.sos.show', $report))
            ->patch(route('monitoring.sos.resolve', $report), ['resolution_notes' => ''])
            ->assertRedirect(route('monitoring.sos.show', $report))
            ->assertSessionHasErrors('resolution_notes');

        $this->assertSame('new', $report->fresh()->status);
    }

    public function test_branch_admin_can_close_own_sos_report_with_notes(): void
    {
        [$admin, $report] = $this->scenario();

        $this->actingAs($admin)
            ->from(route('monitoring.sos.show', $report))
            ->patch(route('monitoring.sos.resolve', $report), [
                'resolution_notes' => 'Jamaah sudah aman bersama Tour Leader di titik kumpul hotel.',
            ])
            ->assertRedirect(route('monitoring.sos.show', $report))
            ->assertSessionHas('success');

        $report->refresh();

        $this->assertSame('resolved', $report->status);
        $this->assertNotNull($report->resolved_at);
        $this->assertSame('normal', $report->pilgrim->fresh()->monitoring_status);
    }

    /**
     * @return array{User, SosReport}
     */
    private function scenario(): array
    {
        $this->seed(RolePermissionSeeder::class);

        $branch = Branch::create(['code' => 'SOS-BJM', 'name' => 'Cabang SOS Banjarmasin', 'city' => 'Banjarmasin']);
        $admin = User::factory()->create(['branch_id' => $branch->id]);
        $admin->assignRole(UserRole::BranchAdmin->value);

        return [$admin, $this->reportForBranch('SOS-BJM-001', $branch->name, $branch)];
    }

    private function reportForBranch(string $registrationNumber, string $branchName, ?Branch $branch = null): SosReport
    {
        $branch ??= Branch::create([
            'code' => strtoupper(substr($registrationNumber, 0, 7)),
            'name' => $branchName,
            'city' => 'Jakarta',
        ]);

        $group = Group::create([
            'branch_id' => $branch->id,
            'code' => $registrationNumber.'-GRP',
            'name' => 'Rombongan '.$branchName,
        ]);
        $pilgrim = Pilgrim::create([
            'branch_id' => $branch->id,
            'group_id' => $group->id,
            'registration_number' => $registrationNumber,
            'full_name' => 'Jamaah '.$registrationNumber,
            'phone' => '081234567890',
            'gender' => 'male',
            'monitoring_status' => 'sos',
            'status' => 'active',
        ]);

        return SosReport::create([
            'branch_id' => $branch->id,
            'pilgrim_id' => $pilgrim->id,
            'group_id' => $group->id,
            'latitude' => 21.422487,
            'longitude' => 39.826206,
            'accuracy' => 8,
            'message' => 'Butuh bantuan di area hotel.',
            'status' => 'new',
            'reported_at' => now(),
        ])->load(['pilgrim', 'group']);
    }
}
