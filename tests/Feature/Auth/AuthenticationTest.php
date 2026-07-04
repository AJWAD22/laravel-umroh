<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = $this->webAdmin();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = $this->webAdmin();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_inactive_users_cannot_authenticate(): void
    {
        $user = $this->webAdmin(['is_active' => false]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_users_without_a_web_admin_role_cannot_authenticate(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_branch_admin_without_a_branch_cannot_authenticate(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['branch_id' => null]);
        $user->assignRole(UserRole::BranchAdmin->value);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_an_active_session_is_terminated_when_the_account_is_disabled(): void
    {
        $user = $this->webAdmin();
        $user->update(['is_active' => false]);

        $response = $this->actingAs($user)->get('/dashboard');

        $this->assertGuest();
        $response
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function webAdmin(array $attributes = []): User
    {
        $this->seed(RolePermissionSeeder::class);

        $branch = Branch::create([
            'code' => fake()->unique()->lexify('???'),
            'name' => fake()->company(),
            'city' => fake()->city(),
        ]);

        $user = User::factory()->create(array_merge([
            'branch_id' => $branch->id,
        ], $attributes));
        $user->assignRole(UserRole::SuperAdmin->value);

        return $user;
    }
}
