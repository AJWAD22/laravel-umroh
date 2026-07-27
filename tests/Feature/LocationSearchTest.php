<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LocationSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_admin_searches_location_through_cached_backend_proxy(): void
    {
        Cache::flush();
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                [
                    'lat' => '21.422487',
                    'lon' => '39.826206',
                    'display_name' => 'Masjidil Haram, Makkah, Arab Saudi',
                ],
            ]),
        ]);
        $this->seed(RolePermissionSeeder::class);
        $branch = Branch::create([
            'code' => 'GEO',
            'name' => 'Cabang Geocoding',
            'city' => 'Banjarmasin',
            'is_active' => true,
        ]);
        $admin = User::factory()->create(['branch_id' => $branch->id]);
        $admin->assignRole(UserRole::BranchAdmin->value);

        $this->actingAs($admin)
            ->getJson(route('locations.search', ['q' => 'Masjidil Haram']))
            ->assertOk()
            ->assertJsonPath('data.0.latitude', 21.422487)
            ->assertJsonPath('data.0.longitude', 39.826206)
            ->assertJsonPath('data.0.label', 'Masjidil Haram, Makkah, Arab Saudi');

        $this->actingAs($admin)
            ->getJson(route('locations.search', ['q' => 'Masjidil Haram']))
            ->assertOk();

        Http::assertSentCount(1);
    }

    public function test_super_admin_cannot_use_branch_operational_location_search(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $superAdmin = User::factory()->create(['branch_id' => null]);
        $superAdmin->assignRole(UserRole::SuperAdmin->value);

        $this->actingAs($superAdmin)
            ->getJson(route('locations.search', ['q' => 'Masjidil Haram']))
            ->assertForbidden();
    }
}
