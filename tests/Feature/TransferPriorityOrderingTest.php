<?php

namespace Tests\Feature;

use App\Models\Priority;
use App\Models\QueueTicket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TransferPriorityOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_priority_tickets_preempt_transfers()
    {
        $txA = Transaction::create(['name' => 'A', 'code' => 'A', 'workflow_order' => 1, 'is_active' => true]);
        $txB = Transaction::create(['name' => 'B', 'code' => 'B', 'workflow_order' => 2, 'is_active' => true]);
        $txC = Transaction::create(['name' => 'C', 'code' => 'C', 'workflow_order' => 3, 'is_active' => true]);

        DB::table('transfer_priority_rules')->insert([
            [
                'rule_key' => 'A_TO_B',
                'from_transaction_id' => $txA->id,
                'to_transaction_id' => $txB->id,
                'priority_score' => 10,
                'sequence' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rule_key' => 'C_TO_B',
                'from_transaction_id' => $txC->id,
                'to_transaction_id' => $txB->id,
                'priority_score' => 5,
                'sequence' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $senior = Priority::create([
            'name' => 'Senior Citizen',
            'code' => 'SC',
            'priority_level' => 1,
            'is_active' => true,
        ]);

        $operator = User::factory()->create(['transaction_id' => $txB->id, 'counter_id' => 1]);
        $this->actingAs($operator);

        $priorityTicket = QueueTicket::create([
            'transaction_id' => $txB->id,
            'priority_id' => $senior->id,
            'queue_number' => 'B0001',
            'status' => 'waiting',
            'daily_sequence' => 1,
        ]);

        $t1 = QueueTicket::create([
            'transaction_id' => $txA->id,
            'queue_number' => 'A0001',
            'status' => 'called',
            'daily_sequence' => 1,
            'called_at' => now(),
        ]);
        $t2 = QueueTicket::create([
            'transaction_id' => $txC->id,
            'queue_number' => 'C0001',
            'status' => 'called',
            'daily_sequence' => 1,
            'called_at' => now(),
        ]);

        $this->postJson(route('queue.my-counter.transfer'), [
            'ticket_id' => $t1->id,
            'transaction_id' => $txB->id,
        ])->assertOk()->assertJson(['success' => true]);

        $this->postJson(route('queue.my-counter.transfer'), [
            'ticket_id' => $t2->id,
            'transaction_id' => $txB->id,
        ])->assertOk()->assertJson(['success' => true]);

        $resp = $this->postJson(route('queue.my-counter.call'));
        $resp->assertOk()->assertJson(['success' => true]);

        $calledId = $resp->json('ticket.id');
        $this->assertSame($priorityTicket->id, $calledId);

        $t1->refresh();
        $this->assertSame('waiting', $t1->status);
        $this->assertSame($txB->id, $t1->transaction_id);
    }

    public function test_calls_highest_priority_transfer_first_when_no_priority_tickets_waiting()
    {
        $txA = Transaction::create(['name' => 'A', 'code' => 'A', 'workflow_order' => 1, 'is_active' => true]);
        $txB = Transaction::create(['name' => 'B', 'code' => 'B', 'workflow_order' => 2, 'is_active' => true]);
        $txC = Transaction::create(['name' => 'C', 'code' => 'C', 'workflow_order' => 3, 'is_active' => true]);

        DB::table('transfer_priority_rules')->insert([
            [
                'rule_key' => 'A_TO_B',
                'from_transaction_id' => $txA->id,
                'to_transaction_id' => $txB->id,
                'priority_score' => 10,
                'sequence' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rule_key' => 'C_TO_B',
                'from_transaction_id' => $txC->id,
                'to_transaction_id' => $txB->id,
                'priority_score' => 5,
                'sequence' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $operator = User::factory()->create(['transaction_id' => $txB->id, 'counter_id' => 1]);
        $this->actingAs($operator);

        $t1 = QueueTicket::create([
            'transaction_id' => $txA->id,
            'queue_number' => 'A0001',
            'status' => 'called',
            'daily_sequence' => 1,
            'called_at' => now(),
        ]);
        $t2 = QueueTicket::create([
            'transaction_id' => $txC->id,
            'queue_number' => 'C0001',
            'status' => 'called',
            'daily_sequence' => 1,
            'called_at' => now(),
        ]);

        $this->postJson(route('queue.my-counter.transfer'), [
            'ticket_id' => $t1->id,
            'transaction_id' => $txB->id,
        ])->assertOk()->assertJson(['success' => true]);

        $this->postJson(route('queue.my-counter.transfer'), [
            'ticket_id' => $t2->id,
            'transaction_id' => $txB->id,
        ])->assertOk()->assertJson(['success' => true]);

        $resp = $this->postJson(route('queue.my-counter.call'));
        $resp->assertOk()->assertJson(['success' => true]);

        $calledId = $resp->json('ticket.id');
        $this->assertSame($t1->id, $calledId);

        $log = DB::table('queue_logs')
            ->where('queue_id', $t1->id)
            ->where('action', 'transfer_prioritized')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('A_TO_B', (string) $log->remarks);
    }

    public function test_transfer_still_preempts_non_transfer_when_no_rules_exist()
    {
        $txA = Transaction::create(['name' => 'A', 'code' => 'A', 'workflow_order' => 1, 'is_active' => true]);
        $txB = Transaction::create(['name' => 'B', 'code' => 'B', 'workflow_order' => 2, 'is_active' => true]);

        $operator = User::factory()->create(['transaction_id' => $txB->id, 'counter_id' => 1]);
        $this->actingAs($operator);

        $normal = QueueTicket::create([
            'transaction_id' => $txB->id,
            'queue_number' => 'B0001',
            'status' => 'waiting',
            'daily_sequence' => 1,
        ]);

        $transfer = QueueTicket::create([
            'transaction_id' => $txA->id,
            'queue_number' => 'A0001',
            'status' => 'called',
            'daily_sequence' => 1,
            'called_at' => now(),
        ]);

        $this->postJson(route('queue.my-counter.transfer'), [
            'ticket_id' => $transfer->id,
            'transaction_id' => $txB->id,
        ])->assertOk();

        $resp = $this->postJson(route('queue.my-counter.call'))->assertOk();
        $this->assertSame($transfer->id, $resp->json('ticket.id'));

        $normal->refresh();
        $this->assertSame('waiting', $normal->status);
    }

    public function test_unmatched_transfer_still_preempts_non_transfer()
    {
        $txA = Transaction::create(['name' => 'A', 'code' => 'A', 'workflow_order' => 1, 'is_active' => true]);
        $txB = Transaction::create(['name' => 'B', 'code' => 'B', 'workflow_order' => 2, 'is_active' => true]);
        $txC = Transaction::create(['name' => 'C', 'code' => 'C', 'workflow_order' => 3, 'is_active' => true]);

        DB::table('transfer_priority_rules')->insert([
            'rule_key' => 'A_TO_C_ONLY',
            'from_transaction_id' => $txA->id,
            'to_transaction_id' => $txC->id,
            'priority_score' => 99,
            'sequence' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $operator = User::factory()->create(['transaction_id' => $txB->id, 'counter_id' => 1]);
        $this->actingAs($operator);

        QueueTicket::create([
            'transaction_id' => $txB->id,
            'queue_number' => 'B0001',
            'status' => 'waiting',
            'daily_sequence' => 1,
        ]);

        $transfer = QueueTicket::create([
            'transaction_id' => $txA->id,
            'queue_number' => 'A0001',
            'status' => 'called',
            'daily_sequence' => 1,
            'called_at' => now(),
        ]);

        $this->postJson(route('queue.my-counter.transfer'), [
            'ticket_id' => $transfer->id,
            'transaction_id' => $txB->id,
        ])->assertOk();

        $resp = $this->postJson(route('queue.my-counter.call'))->assertOk();
        $this->assertSame($transfer->id, $resp->json('ticket.id'));
    }
}
