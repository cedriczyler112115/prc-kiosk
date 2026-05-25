<?php

namespace Tests\Feature;

use App\Models\Priority;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_transaction_types_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create(['access_level_id' => 1]);

        $response = $this->actingAs($user)->get('/libraries/transaction-types');

        $response->assertStatus(200);
        $response->assertSee('Transaction Types');
    }

    public function test_transaction_types_data_endpoint_returns_paginated_json(): void
    {
        $user = User::factory()->create(['access_level_id' => 1]);

        Transaction::create([
            'name' => 'A',
            'code' => 'A1',
            'workflow_order' => 1,
            'is_active' => true,
            'transfer_allowed' => true,
            'priority_enabled' => true,
        ]);
        Transaction::create([
            'name' => 'B',
            'code' => 'B1',
            'workflow_order' => 2,
            'is_active' => true,
            'transfer_allowed' => false,
            'priority_enabled' => false,
        ]);
        Transaction::create([
            'name' => 'C',
            'code' => 'C1',
            'workflow_order' => 3,
            'is_active' => false,
            'transfer_allowed' => true,
            'priority_enabled' => false,
        ]);

        $response = $this->actingAs($user)->getJson('/libraries/transaction-types/data?per_page=10&page=1');

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'meta' => ['page', 'per_page', 'total', 'last_page', 'from', 'to'],
        ]);
        $this->assertSame(3, $response->json('meta.total'));
    }

    public function test_windows_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create(['access_level_id' => 1]);

        $response = $this->actingAs($user)->get('/libraries/windows');

        $response->assertStatus(200);
        $response->assertSee('Windows');
    }

    public function test_windows_data_endpoint_returns_paginated_json(): void
    {
        $user = User::factory()->create(['access_level_id' => 1]);

        User::factory()->create([
            'name' => 'Operator 1',
            'transaction_id' => 1,
            'counter_id' => 1,
        ]);
        User::factory()->create([
            'name' => 'Operator 2',
            'transaction_id' => 1,
            'counter_id' => 2,
        ]);

        $response = $this->actingAs($user)->getJson('/libraries/windows/data?per_page=10&page=1');

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'meta' => ['page', 'per_page', 'total', 'last_page', 'from', 'to'],
        ]);
        // Total should be 3 (authenticated user + 2 operators)
        $this->assertSame(3, $response->json('meta.total'));
    }

    public function test_priorities_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create(['access_level_id' => 1]);

        $response = $this->actingAs($user)->get('/libraries/priorities');

        $response->assertStatus(200);
        $response->assertSee('Priorities');
    }

    public function test_priorities_data_endpoint_returns_paginated_json(): void
    {
        $user = User::factory()->create(['access_level_id' => 1]);

        Priority::create([
            'name' => 'Senior',
            'code' => 'SENIOR',
            'priority_level' => 1,
            'is_active' => true,
        ]);
        Priority::create([
            'name' => 'PWD',
            'code' => 'PWD',
            'priority_level' => 2,
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->getJson('/libraries/priorities/data?per_page=10&page=1');

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'meta' => ['page', 'per_page', 'total', 'last_page', 'from', 'to'],
        ]);
        $this->assertSame(2, $response->json('meta.total'));
    }

    public function test_auth_ping_endpoint_returns_no_content_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/auth/ping');

        $response->assertNoContent();
    }

    public function test_guard_entry_page_renders_with_transactions_and_priorities(): void
    {
        $user = User::factory()->create();

        Transaction::create([
            'name' => 'Permit',
            'code' => 'PRM',
            'workflow_order' => 1,
            'is_active' => true,
            'transfer_allowed' => true,
            'priority_enabled' => true,
        ]);
        Transaction::create([
            'name' => 'Inquiry',
            'code' => 'INQ',
            'workflow_order' => 2,
            'is_active' => true,
            'transfer_allowed' => false,
            'priority_enabled' => false,
        ]);

        Priority::create([
            'name' => 'Senior',
            'code' => 'SENIOR',
            'priority_level' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/queue/guard-entry');
        $response->assertStatus(200);
        $response->assertSee('Guard Queue Entry');
        $response->assertSee('Permit');
        $response->assertSee('Inquiry');
        $response->assertSee('Special Lane');
        $response->assertSee('Senior');
    }
}
