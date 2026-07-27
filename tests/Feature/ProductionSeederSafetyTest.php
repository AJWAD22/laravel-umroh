<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Departure;
use App\Models\Pilgrim;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ProductionSuperAdminSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionSeederSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_database_seeder_does_not_create_or_delete_operational_demo_data(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $branch = Branch::create([
            'code' => 'SAFE',
            'name' => 'Cabang Aman',
            'city' => 'Banjarmasin',
            'is_active' => true,
        ]);
        $pilgrim = Pilgrim::create([
            'branch_id' => $branch->id,
            'registration_number' => 'SAFE-JMH-001',
            'full_name' => 'Jamaah Tetap Aman',
            'gender' => 'male',
            'status' => 'active',
        ]);
        $departure = Departure::create([
            'branch_id' => $branch->id,
            'code' => 'SAFE-DEP-001',
            'program_name' => 'Paket Tetap Aman',
            'departure_date' => today()->addMonth(),
            'return_date' => today()->addMonth()->addDays(9),
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('pilgrims', ['id' => $pilgrim->id]);
        $this->assertDatabaseHas('departures', ['id' => $departure->id]);
        $this->assertDatabaseMissing('users', ['email' => 'superadmin@umrah.test']);
    }

    public function test_production_super_admin_seeder_leaves_only_one_active_super_admin(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $oldAdmin = User::factory()->create([
            'email' => 'superadmin@umrah.test',
            'is_active' => true,
        ]);
        $oldAdmin->assignRole(UserRole::SuperAdmin->value);

        $this->seed(ProductionSuperAdminSeeder::class);

        $primary = User::query()->where('email', 'superadmin@mantauumrah.id')->firstOrFail();
        $this->assertTrue($primary->hasRole(UserRole::SuperAdmin->value));
        $this->assertTrue($primary->is_active);
        $this->assertTrue(Hash::check('password', $primary->password));
        $this->assertFalse($oldAdmin->fresh()->is_active);
        $this->assertFalse($oldAdmin->fresh()->hasRole(UserRole::SuperAdmin->value));
        $this->assertSame(1, User::role(UserRole::SuperAdmin->value)->count());
    }
}
