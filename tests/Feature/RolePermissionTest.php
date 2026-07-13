<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_has_all_permissions(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::SuperAdmin->value);

        $this->assertTrue($user->can('dashboard.view'));
        $this->assertTrue($user->can('indiamart.sync'));
        $this->assertTrue($user->can('roles.delete'));
    }

    public function test_viewer_has_read_only_permissions(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Viewer->value);

        $this->assertTrue($user->can('dashboard.view'));
        $this->assertTrue($user->can('leads.view'));
        $this->assertFalse($user->can('leads.create'));
        $this->assertFalse($user->can('leads.delete'));
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create([
            'email' => 'inactive@gmail.com',
        ]);
        $user->assignRole(RoleName::Viewer->value);

        $response = $this->post('/login', [
            'email' => 'inactive@gmail.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_dashboard_requires_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Viewer->value);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk();
    }
}
