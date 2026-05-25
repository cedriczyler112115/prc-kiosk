<?php

namespace Tests\Feature;

use App\Models\QueueTicket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyCounterAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_endpoint_requires_assignment()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson(route('queue.my-counter.data'));
        $response->assertStatus(403);
    }

    public function test_operator_flow_call_serve_complete()
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $tx = Transaction::create([
            'name' => 'Registration',
            'code' => 'REG',
            'workflow_order' => 1,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'transaction_id' => $tx->id,
            'counter_id' => 1,
        ]);

        $this->actingAs($user);

        $t1 = QueueTicket::create([
            'transaction_id' => $tx->id,
            'queue_number' => 'REG-001',
            'status' => 'waiting',
            'daily_sequence' => 1,
            'created_at' => now()->subMinutes(2),
        ]);

        $t2 = QueueTicket::create([
            'transaction_id' => $tx->id,
            'queue_number' => 'REG-002',
            'status' => 'waiting',
            'daily_sequence' => 2,
            'created_at' => now()->subMinute(),
        ]);

        $call = $this->postJson(route('queue.my-counter.call'));
        $call->assertStatus(200)->assertJson(['success' => true]);
        $t1->refresh();
        $this->assertEquals('called', $t1->status);
        $this->assertEquals(1, $t1->counter_id);
        $this->assertNotNull($t1->called_at);
        $this->assertEquals($user->id, $t1->called_by);

        $serve = $this->postJson(route('queue.my-counter.serve'), ['ticket_id' => $t1->id]);
        $serve->assertStatus(200)->assertJson(['success' => true]);
        $t1->refresh();
        $this->assertEquals('serving', $t1->status);
        $this->assertNotNull($t1->serving_at);
        $this->assertEquals($user->id, $t1->serving_by);

        $complete = $this->postJson(route('queue.my-counter.complete'), ['ticket_id' => $t1->id]);
        $complete->assertStatus(200)->assertJson(['success' => true]);
        $t1->refresh();
        $this->assertEquals('completed', $t1->status);
        $this->assertNotNull($t1->completed_at);
        $this->assertEquals($user->id, $t1->completed_by);
    }
}
