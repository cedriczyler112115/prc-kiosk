<?php

namespace Tests\Feature;

use App\Models\QueueTicket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueTransferIncrementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_transferred_ticket_does_not_cause_duplicate_queue_number_increment()
    {
        $transactionA = Transaction::create([
            'name' => 'Transaction A',
            'code' => 'TRA',
            'is_active' => true,
        ]);

        $transactionB = Transaction::create([
            'name' => 'Transaction B',
            'code' => 'TRB',
            'is_active' => true,
        ]);

        // 1. Customer 1 issues ticket for Transaction A -> TRA001
        $res1 = $this->actingAs($this->user)->postJson(route('queue.guard-entry.store'), [
            'transaction_id' => $transactionA->id,
            'name' => 'Customer 1',
        ]);
        $res1->assertStatus(200);

        $ticket1 = QueueTicket::where('queue_number', 'TRA001')->firstOrFail();
        $this->assertEquals(1, $ticket1->daily_sequence);
        $this->assertEquals($transactionA->id, $ticket1->transaction_id);
        $this->assertEquals($transactionA->id, $ticket1->original_transaction_id);

        // 2. Transfer Ticket 1 (TRA001) from Transaction A to Transaction B
        $transferRes = $this->actingAs($this->user)->postJson(route('queue.my-counter.transfer'), [
            'ticket_id' => $ticket1->id,
            'transaction_id' => $transactionB->id,
            'remarks' => 'Needs Transaction B processing',
        ]);
        $transferRes->assertStatus(200);

        $ticket1->refresh();
        $this->assertEquals($transactionB->id, $ticket1->transaction_id);
        $this->assertEquals($transactionA->id, $ticket1->original_transaction_id);

        // 3. Customer 2 issues ticket for Transaction A -> Should be TRA002 (NOT TRA001)
        $res2 = $this->actingAs($this->user)->postJson(route('queue.guard-entry.store'), [
            'transaction_id' => $transactionA->id,
            'name' => 'Customer 2',
        ]);
        $res2->assertStatus(200);

        $ticket2 = QueueTicket::where('queue_number', 'TRA002')->first();
        $this->assertNotNull($ticket2, 'Ticket TRA002 should exist instead of creating a duplicate TRA001.');
        $this->assertEquals(2, $ticket2->daily_sequence);
        $this->assertEquals($transactionA->id, $ticket2->transaction_id);
        $this->assertEquals($transactionA->id, $ticket2->original_transaction_id);

        // 4. Customer 3 issues another ticket for Transaction A -> Should be TRA003
        $res3 = $this->actingAs($this->user)->postJson(route('queue.guard-entry.store'), [
            'transaction_id' => $transactionA->id,
            'name' => 'Customer 3',
        ]);
        $res3->assertStatus(200);

        $ticket3 = QueueTicket::where('queue_number', 'TRA003')->first();
        $this->assertNotNull($ticket3);
        $this->assertEquals(3, $ticket3->daily_sequence);
    }
}
