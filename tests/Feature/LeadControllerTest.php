<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\LeadSourceSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolePermissionSeeder::class, LeadSourceSeeder::class]);
    }

    public function test_super_admin_can_create_lead_via_api(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::SuperAdmin->value);

        $response = $this->actingAs($user)->postJson(route('leads.store'), [
            'customer_name' => 'John Doe',
            'mobile' => '9123456789',
            'email' => 'john@example.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('leads', [
            'customer_name' => 'John Doe',
            'mobile' => '9123456789',
        ]);
    }

    public function test_marketing_can_create_lead(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Marketing->value);

        $response = $this->actingAs($user)->postJson(route('leads.store'), [
            'customer_name' => 'John Doe',
            'mobile' => '9123456789',
        ]);

        $response->assertCreated()->assertJsonPath('success', true);
    }

    public function test_lead_datatable_returns_paginated_json(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Admin->value);

        Lead::factory()->count(3)->create();

        $response = $this->actingAs($user)->getJson(route('leads.datatable'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta' => ['total', 'current_page']]);
    }

    public function test_manager_sees_all_leads_in_datatable(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Manager->value);

        Lead::factory()->create(['assigned_to' => $user->id, 'customer_name' => 'Mine']);
        Lead::factory()->create(['customer_name' => 'Others']);

        $response = $this->actingAs($user)->getJson(route('leads.datatable'));

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_my_leads_page_only_returns_leads_assigned_to_current_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Manager->value);

        Lead::factory()->create(['assigned_to' => $user->id, 'customer_name' => 'My Lead']);
        Lead::factory()->create(['assigned_to' => null, 'customer_name' => 'Other Lead']);

        $this->actingAs($user)->get(route('leads.my'))
            ->assertOk()
            ->assertSee('My Leads');

        $response = $this->actingAs($user)->getJson(route('leads.my.datatable'));

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $response->assertJsonPath('data.0.customer_name', 'My Lead');
    }

    public function test_all_leads_page_returns_all_leads(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Marketing->value);

        Lead::factory()->create(['assigned_to' => $user->id]);
        Lead::factory()->create(['assigned_to' => null]);

        $this->actingAs($user)->get(route('leads.all'))
            ->assertOk()
            ->assertSee('All Leads');

        $response = $this->actingAs($user)->getJson(route('leads.all.datatable'));

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_first_action_assigns_an_unassigned_lead_to_marketing_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Marketing->value);
        $lead = Lead::factory()->create(['assigned_to' => null]);

        $response = $this->actingAs($user)->postJson(route('leads.actions.store', $lead), [
            'action_type' => 'Call',
            'status' => $lead->status->value,
            'notes' => 'Customer requested a revised quotation.',
            'next_followup_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ]);

        $response->assertCreated()->assertJsonPath('success', true);
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'assigned_to' => $user->id,
        ]);
        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type' => 'Call',
            'causer_id' => $user->id,
        ]);
        $this->assertDatabaseHas('lead_followups', [
            'lead_id' => $lead->id,
            'type' => 'Call',
            'status' => 'Completed',
            'assigned_to' => $user->id,
        ]);
    }

    public function test_admin_can_choose_assignee_when_recording_first_action(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin->value);
        $assignee = User::factory()->create();
        $lead = Lead::factory()->create(['assigned_to' => null]);

        $response = $this->actingAs($admin)->postJson(route('leads.actions.store', $lead), [
            'action_type' => 'Meeting',
            'status' => $lead->status->value,
            'notes' => 'Initial meeting completed.',
            'assigned_to' => $assignee->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'assigned_to' => $assignee->id,
        ]);
    }

    public function test_non_admin_cannot_forge_action_assignee(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::Marketing->value);
        $otherUser = User::factory()->create();
        $lead = Lead::factory()->create(['assigned_to' => null]);

        $response = $this->actingAs($user)->postJson(route('leads.actions.store', $lead), [
            'action_type' => 'WhatsApp',
            'status' => $lead->status->value,
            'notes' => 'Customer replied.',
            'assigned_to' => $otherUser->id,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('assigned_to');
        $this->assertNull($lead->fresh()->assigned_to);
    }
}
