<?php

namespace Tests\Feature;

use App\Models\QueueTicket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TransferredServiceTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_service_time_is_recorded_with_audit_and_only_once()
    {
        $from = Transaction::create(['name' => 'From', 'code' => 'FRM', 'is_active' => true]);
        $to = Transaction::create(['name' => 'To', 'code' => 'TO', 'is_active' => true]);

        $operator = User::factory()->create(['transaction_id' => $to->id, 'counter_id' => 7]);
        $this->actingAs($operator);

        // Transferred ticket already waiting in destination
        $t = QueueTicket::create([
            'transaction_id' => $to->id,
            'queue_number' => 'TO-001',
            'status' => 'waiting',
            'daily_sequence' => 1,
            'is_transfer' => true,
        ]);

        // Call next to set called_at, then start serving (mark service start)
        $this->postJson(route('queue.my-counter.call'))->assertStatus(200);

        // Freeze time and serve
        Carbon::setTestNow(Carbon::parse('2026-03-06 10:00:00'));
        $this->postJson(route('queue.my-counter.serve'), ['ticket_id' => $t->id])->assertStatus(200);
        $t->refresh();
        $this->assertNotNull($t->transfer_service_started_at);

        // Advance time and complete
        $start = $t->transfer_service_started_at;
        Carbon::setTestNow(Carbon::parse('2026-03-06 10:05:30'));
        $this->postJson(route('queue.my-counter.complete'), ['ticket_id' => $t->id])->assertStatus(200);
        $t->refresh();
        $this->assertNotNull($t->transfer_service_time_seconds);
        $this->assertGreaterThan(0, $t->transfer_service_time_seconds);
        $this->assertNotNull($t->transfer_service_completed_at);

        // Ensure audit logs exist
        $this->assertDatabaseHas('queue_logs', [
            'queue_id' => $t->id,
            'action' => 'transfer_service_started',
        ]);
        $this->assertDatabaseHas('queue_logs', [
            'queue_id' => $t->id,
            'action' => 'transfer_service_recorded',
        ]);

        // Try to "recompute": set status back to serving would violate flow; instead ensure idempotency by setting a pre-existing value earlier and completing again in a fresh ticket
        $t2 = QueueTicket::create([
            'transaction_id' => $to->id,
            'queue_number' => 'TO-002',
            'status' => 'called',
            'daily_sequence' => 2,
            'is_transfer' => true,
            'called_at' => Carbon::parse('2026-03-06 11:00:00'),
        ]);
        // Serve and set a manual pre-existing transfer time
        Carbon::setTestNow(Carbon::parse('2026-03-06 11:01:00'));
        $this->postJson(route('queue.my-counter.serve'), ['ticket_id' => $t2->id])->assertStatus(200);
        $t2->refresh();
        $t2->transfer_service_started_at = Carbon::parse('2026-03-06 11:01:00');
        $t2->transfer_service_time_seconds = 60;
        $t2->save();

        // Complete later; the controller must not overwrite the existing metric
        Carbon::setTestNow(Carbon::parse('2026-03-06 11:10:00'));
        $this->postJson(route('queue.my-counter.complete'), ['ticket_id' => $t2->id])->assertStatus(200);
        $t2->refresh();
        $this->assertSame(60, $t2->transfer_service_time_seconds);

        Carbon::setTestNow();
    }
}
