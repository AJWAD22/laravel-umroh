<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Events\AdminNotificationCreated;
use App\Models\Branch;
use App\Models\Notification;
use App\Models\Pilgrim;
use App\Models\PilgrimLocation;
use App\Models\User;
use App\Services\AdminNotificationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_gps_offline_and_geofence_alerts_reach_the_correct_admins(): void
    {
        Event::fake([AdminNotificationCreated::class]);
        [$superAdmin, $branchAdmin, $foreignAdmin, $pilgrim] = $this->scenario();

        PilgrimLocation::create([
            'pilgrim_id' => $pilgrim->id,
            'latitude' => 21.4224870,
            'longitude' => 39.8262060,
            'gps_status' => 'offline',
            'recorded_at' => now()->subMinutes(10),
        ]);

        app(AdminNotificationService::class)->geofenceExit(
            $pilgrim,
            21.4300000,
            39.8300000,
            'Hotel Jamaah',
        );

        $this->assertSame(2, Notification::where('notifiable_id', $branchAdmin->id)->count());
        $this->assertSame(0, Notification::where('notifiable_id', $superAdmin->id)->count());
        $this->assertSame(0, Notification::where('notifiable_id', $foreignAdmin->id)->count());
        $this->assertEqualsCanonicalizing(
            ['gps_offline', 'geofence_exit'],
            Notification::where('notifiable_id', $branchAdmin->id)->pluck('type')->all(),
        );

        Event::assertDispatched(
            AdminNotificationCreated::class,
            fn (AdminNotificationCreated $event) => $event->type === 'geofence_exit',
        );
    }

    public function test_admin_can_read_own_notifications_but_not_another_users(): void
    {
        Event::fake([AdminNotificationCreated::class]);
        [, $branchAdmin, $foreignAdmin, $pilgrim] = $this->scenario();
        app(AdminNotificationService::class)->geofenceExit($pilgrim, 21.4, 39.8, 'Area Aman');

        $ownNotification = Notification::where('notifiable_id', $branchAdmin->id)->firstOrFail();
        $foreignNotification = Notification::create([
            'id' => fake()->uuid(),
            'branch_id' => $foreignAdmin->branch_id,
            'type' => 'gps_offline',
            'notifiable_type' => User::class,
            'notifiable_id' => $foreignAdmin->id,
            'data' => ['title' => 'Rahasia', 'message' => 'Data cabang lain'],
        ]);

        $this->actingAs($branchAdmin)
            ->patch(route('notifications.read', $ownNotification))
            ->assertRedirect();
        $this->assertNotNull($ownNotification->fresh()->read_at);

        $this->actingAs($branchAdmin)
            ->patch(route('notifications.read', $foreignNotification))
            ->assertForbidden();
        $this->assertNull($foreignNotification->fresh()->read_at);
    }

    public function test_admin_can_delete_own_notifications_but_not_another_users(): void
    {
        Event::fake([AdminNotificationCreated::class]);
        [, $branchAdmin, $foreignAdmin, $pilgrim] = $this->scenario();
        app(AdminNotificationService::class)->geofenceExit($pilgrim, 21.4, 39.8, 'Area Aman');

        $ownNotification = Notification::where('notifiable_id', $branchAdmin->id)->firstOrFail();
        $foreignNotification = Notification::create([
            'id' => fake()->uuid(),
            'branch_id' => $foreignAdmin->branch_id,
            'type' => 'gps_offline',
            'notifiable_type' => User::class,
            'notifiable_id' => $foreignAdmin->id,
            'data' => ['title' => 'Rahasia', 'message' => 'Data cabang lain'],
        ]);

        $this->actingAs($branchAdmin)
            ->delete(route('notifications.destroy', $ownNotification))
            ->assertRedirect();
        $this->assertDatabaseMissing('notifications', ['id' => $ownNotification->id]);

        $this->actingAs($branchAdmin)
            ->delete(route('notifications.destroy', $foreignNotification))
            ->assertForbidden();
        $this->assertDatabaseHas('notifications', ['id' => $foreignNotification->id]);
    }

    /**
     * @return array{User, User, User, Pilgrim}
     */
    private function scenario(): array
    {
        $this->seed(RolePermissionSeeder::class);
        $branchA = Branch::create(['code' => 'NTF-A', 'name' => 'Cabang Notifikasi A', 'city' => 'Makassar']);
        $branchB = Branch::create(['code' => 'NTF-B', 'name' => 'Cabang Notifikasi B', 'city' => 'Jakarta']);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(UserRole::SuperAdmin->value);
        $branchAdmin = User::factory()->create(['branch_id' => $branchA->id]);
        $branchAdmin->assignRole(UserRole::BranchAdmin->value);
        $foreignAdmin = User::factory()->create(['branch_id' => $branchB->id]);
        $foreignAdmin->assignRole(UserRole::BranchAdmin->value);

        $pilgrim = Pilgrim::create([
            'branch_id' => $branchA->id,
            'registration_number' => 'NTF-JMH-001',
            'full_name' => 'Jamaah Notifikasi',
            'gender' => 'male',
            'status' => 'active',
        ]);

        return [$superAdmin, $branchAdmin, $foreignAdmin, $pilgrim];
    }
}
