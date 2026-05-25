<?php

namespace Tests\Feature;

use App\Models\QueueTicket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedCounterTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_counter_blocks_concurrent_calls()
    {
        $tx = Transaction::create([
            'name' => 'Registration',
            'code' => 'REG',
            'is_active' => true,
        ]);

        // User 1 on Counter 1
        $user1 = User::factory()->create([
            'transaction_id' => $tx->id,
            'counter_id' => 1,
        ]);

        // User 2 on Counter 1 (same counter)
        $user2 = User::factory()->create([
            'transaction_id' => $tx->id,
            'counter_id' => 1,
        ]);

        // Create two waiting tickets
        $t1 = QueueTicket::create([
            'transaction_id' => $tx->id,
            'queue_number' => 'A001',
            'status' => 'waiting',
            'daily_sequence' => 1,
            'created_at' => now()->subMinutes(10),
        ]);

        $t2 = QueueTicket::create([
            'transaction_id' => $tx->id,
            'queue_number' => 'A002',
            'status' => 'waiting',
            'daily_sequence' => 2,
            'created_at' => now()->subMinutes(5),
        ]);

        // User 1 calls next
        $response1 = $this->actingAs($user1)->postJson(route('queue.my-counter.call'));
        $response1->assertStatus(200);

        $t1->refresh();
        $this->assertEquals('called', $t1->status);
        $this->assertEquals(1, $t1->counter_id);

        // User 2 tries to call next
        $response2 = $this->actingAs($user2)->postJson(route('queue.my-counter.call'));

        // Should be blocked because Counter 1 is busy
        $response2->assertStatus(400);
        $response2->assertJson(['error' => 'You have an active ticket. Please complete or transfer it first.']);

        // Ticket 2 should still be waiting
        $t2->refresh();
        $this->assertEquals('waiting', $t2->status);
        $this->assertNull($t2->counter_id);
    }

    public function test_counter_sees_active_ticket_from_different_transaction()
    {
        $tx1 = Transaction::create(['name' => 'Tx1', 'code' => 'T1', 'is_active' => true]);
        $tx2 = Transaction::create(['name' => 'Tx2', 'code' => 'T2', 'is_active' => true]);

        // User 1 on Counter 1, assigned to Tx1
        $user1 = User::factory()->create([
            'transaction_id' => $tx1->id,
            'counter_id' => 1,
        ]);

        // Create a ticket for Tx2 that is already being served by Counter 1 (maybe by another user or previous assignment)
        $t_active = QueueTicket::create([
            'transaction_id' => $tx2->id,
            'queue_number' => 'T2-001',
            'status' => 'serving',
            'counter_id' => 1, // Counter 1 is busy with this
            'daily_sequence' => 1,
            'created_at' => now()->subMinutes(5),
        ]);

        // Create a waiting ticket for Tx1
        $t_waiting = QueueTicket::create([
            'transaction_id' => $tx1->id,
            'queue_number' => 'T1-001',
            'status' => 'waiting',
            'daily_sequence' => 1,
            'created_at' => now()->subMinutes(10),
        ]);

        // User 1 checks data
        $response = $this->actingAs($user1)->getJson(route('queue.my-counter.data'));
        $response->assertStatus(200);

        // Should see the active ticket even though it's Tx2
        $this->assertEquals($t_active->id, $response->json('current.id'));

        // User 1 tries to call next
        $call = $this->actingAs($user1)->postJson(route('queue.my-counter.call'));

        // Should be blocked
        $call->assertStatus(400);
    }
}
