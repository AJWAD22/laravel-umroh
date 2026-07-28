<?php

namespace Tests\Feature;

use App\Models\Departure;
use App\Models\Group;
use App\Models\Muthawwif;
use App\Models\Pilgrim;
use App\Models\TourLeader;
use App\Models\User;
use Database\Seeders\DemoMasterDataSeeder;
use Database\Seeders\RemoveDemoOperationalDataSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemoveDemoOperationalDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_removes_only_demo_operational_data_and_keeps_branch_admin(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(DemoMasterDataSeeder::class);

        $this->seed(RemoveDemoOperationalDataSeeder::class);

        $this->assertSame(0, Pilgrim::query()->count());
        $this->assertSame(0, TourLeader::query()->count());
        $this->assertSame(0, Muthawwif::query()->count());
        $this->assertSame(0, Group::query()->count());
        $this->assertSame(0, Departure::query()->count());
        $this->assertNotNull(User::query()->where('email', 'adminbjm@mantauumrah.id')->first());
        $this->assertDatabaseMissing('users', ['email' => 'padilbjm@mantauumrah.id']);
        $this->assertDatabaseMissing('users', ['email' => 'hafisbjm@mantauumrah.id']);
    }
}
