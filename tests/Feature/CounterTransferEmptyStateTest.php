<?php

namespace Tests\Feature;

use App\Models\QueueTicket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CounterTransferEmptyStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_clears_active_ticket_and_counter_is_ready_for_next_ticket()
    {
        $txA = Transaction::create(['name' => 'Transaction A', 'code' => 'TRA', 'is_active' => true]);
        $txB = Transaction::create(['name' => 'Transaction B', 'code' => 'TRB', 'is_active' => true]);

        $counterUser = User::factory()->create([
            'transaction_id' => $txA->id,
            'counter_id' => '1',
        ]);

        // 1. Create 2 waiting tickets for Transaction A: TRA001 and TRA002
        $ticket1 = QueueTicket::create([
            'transaction_id' => $txA->id,
            'original_transaction_id' => $txA->id,
            'queue_number' => 'TRA001',
            'daily_sequence' => 1,
            'status' => 'waiting',
        ]);

        $ticket2 = QueueTicket::create([
            'transaction_id' => $txA->id,
            'original_transaction_id' => $txA->id,
            'queue_number' => 'TRA002',
            'daily_sequence' => 2,
            'status' => 'waiting',
        ]);

        // 2. Call first ticket (TRA001)
        $call1 = $this->actingAs($counterUser)->postJson(route('queue.my-counter.call'));
        $call1->assertStatus(200)->assertJsonPath('ticket.queue_number', 'TRA001');

        // 3. Transfer TRA001 to Transaction B
        $transferRes = $this->actingAs($counterUser)->postJson(route('queue.my-counter.transfer'), [
            'ticket_id' => $ticket1->id,
            'transaction_id' => $txB->id,
            'remarks' => 'Transferring to B',
        ]);
        $transferRes->assertStatus(200)->assertJson(['success' => true]);

        // 4. Verify myCounterData returns current = null (empty active ticket state)
        $dataRes = $this->actingAs($counterUser)->getJson(route('queue.my-counter.data'));
        $dataRes->assertStatus(200);
        $this->assertNull($dataRes->json('current'), 'Current active ticket should be null (empty) after transfer.');

        // 5. Counter is immediately ready to accommodate next ticket (TRA002)
        $call2 = $this->actingAs($counterUser)->postJson(route('queue.my-counter.call'));
        $call2->assertStatus(200)->assertJsonPath('ticket.queue_number', 'TRA002');
    }
}
