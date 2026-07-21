<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\LeadSourceSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowupControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolePermissionSeeder::class, LeadSourceSeeder::class]);
    }

    public function test_my_followups_only_returns_current_users_dated_followups(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Marketing->value);

        Lead::factory()->create([
            'assigned_to' => $user->id,
            'customer_name' => 'My Followup',
            'next_followup_at' => now()->addDay(),
        ]);
        Lead::factory()->create([
            'assigned_to' => null,
            'customer_name' => 'Other Followup',
            'next_followup_at' => now()->addDays(2),
        ]);
        Lead::factory()->create([
            'assigned_to' => $user->id,
            'customer_name' => 'No Followup',
            'next_followup_at' => null,
        ]);

        $this->actingAs($user)->get(route('followups.my'))
            ->assertOk()
            ->assertSee('My Followups');

        $response = $this->actingAs($user)->getJson(route('followups.my.datatable'));

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $response->assertJsonPath('data.0.customer_name', 'My Followup');
    }

    public function test_all_followups_returns_latest_followup_date_first(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Manager->value);

        Lead::factory()->create([
            'customer_name' => 'Earlier Followup',
            'next_followup_at' => now()->addDay(),
        ]);
        Lead::factory()->create([
            'customer_name' => 'Latest Followup',
            'next_followup_at' => now()->addDays(3),
        ]);
        Lead::factory()->create([
            'customer_name' => 'No Followup',
            'next_followup_at' => null,
        ]);

        $this->actingAs($user)->get(route('followups.all'))
            ->assertOk()
            ->assertSee('All Followups');

        $response = $this->actingAs($user)->getJson(route('followups.all.datatable'));

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $response->assertJsonPath('data.0.customer_name', 'Latest Followup');
        $response->assertJsonPath('data.1.customer_name', 'Earlier Followup');
    }
}
