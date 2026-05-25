<?php

namespace Tests\Feature;

use App\Models\QueueTicket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QueueLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_created_on_ticket_creation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $tx = Transaction::create(['name' => 'Tx', 'code' => 'TX', 'is_active' => true]);

        $resp = $this->postJson(route('queue.guard-entry.store'), [
            'transaction_id' => $tx->id,
        ]);
        $resp->assertStatus(200);

        $ticketId = QueueTicket::first()->id;
        $this->assertDatabaseHas('queue_logs', [
            'queue_id' => $ticketId,
            'action' => 'created',
            'new_status' => 'waiting',
        ]);
    }

    public function test_logs_created_on_call_serve_complete_and_no_log_on_invalid_serve()
    {
        $tx = Transaction::create(['name' => 'Tx', 'code' => 'TX', 'is_active' => true]);
        $user = User::factory()->create(['transaction_id' => $tx->id, 'counter_id' => 1]);
        $this->actingAs($user);

        $t = QueueTicket::create([
            'transaction_id' => $tx->id,
            'queue_number' => 'TX-001',
            'status' => 'waiting',
            'daily_sequence' => 1,
        ]);

        $call = $this->postJson(route('queue.my-counter.call'));
        $call->assertStatus(200)->assertJson(['success' => true]);
        $t->refresh();
        $this->assertDatabaseHas('queue_logs', [
            'queue_id' => $t->id,
            'action' => 'called',
            'old_status' => 'waiting',
            'new_status' => 'called',
        ]);

        $serve = $this->postJson(route('queue.my-counter.serve'), ['ticket_id' => $t->id]);
        $serve->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('queue_logs', [
            'queue_id' => $t->id,
            'action' => 'serving',
            'new_status' => 'serving',
        ]);

        $complete = $this->postJson(route('queue.my-counter.complete'), ['ticket_id' => $t->id]);
        $complete->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('queue_logs', [
            'queue_id' => $t->id,
            'action' => 'completed',
            'new_status' => 'completed',
        ]);

        $t2 = QueueTicket::create([
            'transaction_id' => $tx->id,
            'queue_number' => 'TX-002',
            'status' => 'waiting',
            'daily_sequence' => 2,
        ]);
        $invalid = $this->postJson(route('queue.my-counter.serve'), ['ticket_id' => $t2->id]);
        $invalid->assertStatus(400);
        $this->assertDatabaseCount('queue_logs', DB::table('queue_logs')->count());
        $this->assertDatabaseMissing('queue_logs', [
            'queue_id' => $t2->id,
            'action' => 'serving',
        ]);
    }
}
