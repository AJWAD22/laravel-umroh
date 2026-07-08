<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Events\AdminNotificationCreated;
use App\Models\Branch;
use App\Models\LocationHistory;
use App\Models\Pilgrim;
use App\Models\SosReport;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_download_pdf_and_excel_reports(): void
    {
        [$superAdmin] = $this->scenario();
        $filters = [
            'date_from' => today()->startOfMonth()->toDateString(),
            'date_to' => today()->toDateString(),
        ];

        $this->actingAs($superAdmin)
            ->get(route('reports.download', ['type' => 'pilgrims', 'format' => 'pdf', ...$filters]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload();

        $this->actingAs($superAdmin)
            ->get(route('reports.download', ['type' => 'pilgrims', 'format' => 'xlsx', ...$filters]))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertDownload();
    }

    public function test_branch_admin_preview_is_isolated_for_all_report_types(): void
    {
        [, $branchAdmin, $foreignBranch] = $this->scenario();
        $filters = [
            'date_from' => today()->startOfMonth()->toDateString(),
            'date_to' => today()->toDateString(),
            'branch_id' => $foreignBranch->id,
        ];

        $expectations = [
            'pilgrims' => ['Jamaah Laporan A', 'Jamaah Rahasia B'],
            'tracking' => ['Jamaah Laporan A', 'Jamaah Rahasia B'],
            'sos' => ['Jamaah Laporan A', 'Jamaah Rahasia B'],
        ];

        foreach ($expectations as $type => [$visible, $hidden]) {
            $this->actingAs($branchAdmin)
                ->get(route('reports.index', ['type' => $type, ...$filters]))
                ->assertOk()
                ->assertSee($visible)
                ->assertDontSee($hidden);
        }
    }

    public function test_all_report_pages_render_with_default_filters(): void
    {
        [$superAdmin] = $this->scenario();

        foreach (['pilgrims', 'tracking', 'sos'] as $type) {
            $this->actingAs($superAdmin)
                ->get(route('reports.index', $type))
                ->assertOk()
                ->assertSee('Export PDF')
                ->assertSee('Export Excel');
        }
    }

    /**
     * @return array{User, User, Branch}
     */
    private function scenario(): array
    {
        Event::fake([AdminNotificationCreated::class]);
        $this->seed(RolePermissionSeeder::class);
        $branchA = Branch::firstOrCreate(['code' => 'RPT-A'], ['name' => 'Cabang Laporan A', 'city' => 'Makassar']);
        $branchB = Branch::firstOrCreate(['code' => 'RPT-B'], ['name' => 'Cabang Laporan B', 'city' => 'Jakarta']);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(UserRole::SuperAdmin->value);
        $branchAdmin = User::factory()->create(['branch_id' => $branchA->id]);
        $branchAdmin->assignRole(UserRole::BranchAdmin->value);

        $pilgrimA = $this->pilgrim($branchA, 'RPT-JMH-A', 'Jamaah Laporan A');
        $pilgrimB = $this->pilgrim($branchB, 'RPT-JMH-B', 'Jamaah Rahasia B');
        $this->tracking($pilgrimA, 21.4224, 39.8262);
        $this->tracking($pilgrimB, 24.4672, 39.6111);
        $this->sos($branchA, $pilgrimA);
        $this->sos($branchB, $pilgrimB);

        return [$superAdmin, $branchAdmin, $branchB];
    }

    private function pilgrim(Branch $branch, string $number, string $name): Pilgrim
    {
        return Pilgrim::firstOrCreate(['registration_number' => $number], [
            'branch_id' => $branch->id,
            'full_name' => $name,
            'gender' => 'male',
            'status' => 'active',
        ]);
    }

    private function tracking(Pilgrim $pilgrim, float $latitude, float $longitude): void
    {
        LocationHistory::firstOrCreate([
            'pilgrim_id' => $pilgrim->id,
            'recorded_at' => today()->setTime(8, 0),
        ], [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => 5,
        ]);
    }

    private function sos(Branch $branch, Pilgrim $pilgrim): void
    {
        SosReport::firstOrCreate([
            'branch_id' => $branch->id,
            'pilgrim_id' => $pilgrim->id,
        ], [
            'latitude' => 21.4224,
            'longitude' => 39.8262,
            'status' => 'active',
            'reported_at' => now(),
        ]);
    }

}
