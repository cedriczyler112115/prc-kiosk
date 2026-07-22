<?php

namespace Tests\Feature;

use App\Models\QueueTicket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SkippedTransferPlacementTest extends TestCase
{
    use RefreshDatabase;

    public function test_newest_transferred_ticket_is_placed_last_by_transfer_datetime()
    {
        $txA = Transaction::create(['name' => 'Transaction A', 'code' => 'TRA', 'is_active' => true]);
        $txB = Transaction::create(['name' => 'Transaction B', 'code' => 'TRB', 'is_active' => true]);
        $txC = Transaction::create(['name' => 'Transaction C', 'code' => 'TRC', 'is_active' => true]);

        $staffUser = User::factory()->create([
            'transaction_id' => $txB->id,
            'counter_id' => '1',
        ]);

        // 1. Create a skipped ticket TRA001 for Transaction A at 9:00 AM
        Carbon::setTestNow('2026-07-22 09:00:00');
        $ticketA1 = QueueTicket::create([
            'transaction_id' => $txA->id,
            'original_transaction_id' => $txA->id,
            'queue_number' => 'TRA001',
            'daily_sequence' => 1,
            'status' => 'skipped',
            'skipped_by' => $staffUser->id,
        ]);

        // 2. Transfer TRA001 (recalled from skipped) to Transaction B at 9:10 AM
        Carbon::setTestNow('2026-07-22 09:10:00');
        $this->actingAs($staffUser)->postJson(route('queue.my-counter.transfer'), [
            'ticket_id' => $ticketA1->id,
            'transaction_id' => $txB->id,
            'remarks' => 'Transferring recalled skipped ticket',
        ])->assertStatus(200);

        // 3. Create another ticket TRC001 for Transaction C and transfer it to Transaction B at 9:15 AM (newer transfer)
        Carbon::setTestNow('2026-07-22 09:15:00');
        $ticketC1 = QueueTicket::create([
            'transaction_id' => $txC->id,
            'original_transaction_id' => $txC->id,
            'queue_number' => 'TRC001',
            'daily_sequence' => 1,
            'status' => 'called',
        ]);

        $this->actingAs($staffUser)->postJson(route('queue.my-counter.transfer'), [
            'ticket_id' => $ticketC1->id,
            'transaction_id' => $txB->id,
            'remarks' => 'Newer transfer at 9:15 AM',
        ])->assertStatus(200);

        // 4. Verify waiting list order for Transaction B:
        // TRA001 (transferred 9:10 AM) should come BEFORE TRC001 (transferred 9:15 AM).
        // The newest transfer (TRC001) MUST BE LAST!
        $dataRes = $this->actingAs($staffUser)->getJson(route('queue.my-counter.data'));
        $dataRes->assertStatus(200);

        $waitingList = $dataRes->json('waiting');
        $waitingNumbers = array_column($waitingList, 'queue_number');

        $this->assertEquals(['TRA001', 'TRC001'], $waitingNumbers);

        // Call next ticket -> TRA001 should be called first, TRC001 (the newest transfer) called last
        $call1 = $this->actingAs($staffUser)->postJson(route('queue.my-counter.call'));
        $call1->assertStatus(200)->assertJsonPath('ticket.queue_number', 'TRA001');
    }
}
