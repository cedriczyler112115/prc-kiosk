<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_has_full_access()
    {
        // Need transaction for my-counter route
        $transaction = \App\Models\Transaction::create([
            'name' => 'Test Transaction',
            'code' => 'TEST',
            'is_active' => true
        ]);
        
        $admin = User::factory()->create([
            'access_level_id' => 1,
            'transaction_id' => $transaction->id,
            'counter_id' => 1, // Just an integer ID
        ]);

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('queue.guard-entry'))->assertOk();
        $this->actingAs($admin)->get(route('queue.my-counter'))->assertOk();
        $this->actingAs($admin)->get(route('libraries.transaction-types'))->assertOk();
        $this->actingAs($admin)->get(route('libraries.windows'))->assertOk();
    }

    public function test_staff_cannot_access_libraries()
    {
        $transaction = \App\Models\Transaction::create([
            'name' => 'Test Transaction Staff',
            'code' => 'TEST_STAFF',
            'is_active' => true
        ]);
        
        $staff = User::factory()->create([
            'access_level_id' => 2,
            'transaction_id' => $transaction->id,
            'counter_id' => 1, // Just an integer ID
        ]);

        $this->actingAs($staff)->get(route('dashboard'))->assertOk();
        $this->actingAs($staff)->get(route('queue.guard-entry'))->assertOk();
        $this->actingAs($staff)->get(route('queue.my-counter'))->assertOk();
        
        $this->actingAs($staff)->get(route('libraries.transaction-types'))->assertForbidden();
        $this->actingAs($staff)->get(route('libraries.windows'))->assertForbidden();
    }

    public function test_guard_cannot_access_queue_management_and_libraries()
    {
        $guard = User::factory()->create(['access_level_id' => 3]);

        $this->actingAs($guard)->get(route('dashboard'))->assertOk();
        $this->actingAs($guard)->get(route('queue.guard-entry'))->assertOk();
        
        $this->actingAs($guard)->get(route('queue.my-counter'))->assertForbidden();
        $this->actingAs($guard)->get(route('queue.list'))->assertForbidden();
        $this->actingAs($guard)->get(route('libraries.transaction-types'))->assertForbidden();
        $this->actingAs($guard)->get(route('libraries.windows'))->assertForbidden();
    }

    public function test_unauthenticated_user_redirected_to_login()
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('libraries.transaction-types'))->assertRedirect(route('login'));
    }
}
