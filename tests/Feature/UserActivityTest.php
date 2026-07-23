<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Enums\UserActivityType;
use App\Models\User;
use App\Models\UserActivity;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_login_stores_user_activity_and_last_login(): void
    {
        $user = User::factory()->create([
            'email' => 'activity@example.com',
            'password' => 'password',
        ]);
        $user->assignRole(RoleName::Marketing->value);

        $this->post(route('login'), [
            'login' => 'activity@example.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('user_activities', [
            'user_id' => $user->id,
            'type' => UserActivityType::Login->value,
        ]);
    }

    public function test_super_admin_can_view_user_activity_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::SuperAdmin->value);

        UserActivity::query()->create([
            'user_id' => $admin->id,
            'type' => UserActivityType::Login->value,
            'title' => UserActivityType::Login->value,
            'description' => 'Logged in',
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($admin)->get(route('user-activities.index'))
            ->assertOk()
            ->assertSee('User Activity');

        $this->actingAs($admin)->getJson(route('user-activities.datatable'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_non_super_admin_cannot_view_user_activity_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Admin->value);

        $this->actingAs($user)->get(route('user-activities.index'))
            ->assertForbidden();
    }

    public function test_users_datatable_includes_last_login(): void
    {
        $admin = User::factory()->create([
            'last_login_at' => now()->subHour(),
        ]);
        $admin->assignRole(RoleName::Admin->value);

        $response = $this->actingAs($admin)->getJson(route('users.datatable'));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotEmpty($response->json('data.0.last_login_at'));
    }
}
