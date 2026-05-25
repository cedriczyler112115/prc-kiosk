<?php

namespace Tests\Feature;

use App\Models\QueueTicket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QueueTransferAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_creates_transfer_record_on_success()
    {
        $from = Transaction::create(['name' => 'From', 'code' => 'FRM', 'is_active' => true]);
        $to = Transaction::create(['name' => 'To', 'code' => 'TO', 'is_active' => true]);

        $operator = User::factory()->create(['transaction_id' => $from->id, 'counter_id' => 1]);
        $this->actingAs($operator);

        $ticket = QueueTicket::create([
            'transaction_id' => $from->id,
            'queue_number' => 'FRM-001',
            'status' => 'called',
            'daily_sequence' => 1,
            'called_at' => now(),
        ]);

        $resp = $this->postJson(route('queue.my-counter.transfer'), [
            'ticket_id' => $ticket->id,
            'transaction_id' => $to->id,
            'remarks' => 'Operator request',
        ]);
        $resp->assertStatus(200)->assertJson(['success' => true]);

        $ticket->refresh();
        $this->assertEquals('waiting', $ticket->status);
        $this->assertEquals($to->id, $ticket->transaction_id);

        $this->assertDatabaseHas('queue_transfers', [
            'queue_id' => $ticket->id,
            'from_transaction_id' => $from->id,
            'to_transaction_id' => $to->id,
            'transferred_by' => $operator->id,
            'remarks' => 'Operator request',
        ]);
    }

    public function test_does_not_create_record_on_validation_failure()
    {
        $from = Transaction::create(['name' => 'From', 'code' => 'FRM', 'is_active' => true]);

        $operator = User::factory()->create(['transaction_id' => $from->id, 'counter_id' => 1]);
        $this->actingAs($operator);

        $ticket = QueueTicket::create([
            'transaction_id' => $from->id,
            'queue_number' => 'FRM-001',
            'status' => 'waiting',
            'daily_sequence' => 1,
        ]);

        $resp = $this->postJson(route('queue.my-counter.transfer'), [
            'ticket_id' => $ticket->id,
            'transaction_id' => 999999,
        ]);
        $resp->assertStatus(422);

        $this->assertDatabaseCount('queue_transfers', 0);
    }

    public function test_transfer_record_values_are_accurate()
    {
        $from = Transaction::create(['name' => 'From', 'code' => 'FRM', 'is_active' => true]);
        $to = Transaction::create(['name' => 'To', 'code' => 'TO', 'is_active' => true]);

        $operator = User::factory()->create(['transaction_id' => $from->id, 'counter_id' => 2]);
        $this->actingAs($operator);

        $ticket = QueueTicket::create([
            'transaction_id' => $from->id,
            'queue_number' => 'FRM-002',
            'status' => 'called',
            'daily_sequence' => 2,
            'called_at' => now(),
        ]);

        $resp = $this->postJson(route('queue.my-counter.transfer'), [
            'ticket_id' => $ticket->id,
            'transaction_id' => $to->id,
            'remarks' => null,
        ]);
        $resp->assertStatus(200)->assertJson(['success' => true]);

        $row = DB::table('queue_transfers')->where('queue_id', $ticket->id)->first();
        $this->assertNotNull($row);
        $this->assertEquals($from->id, $row->from_transaction_id);
        $this->assertEquals($to->id, $row->to_transaction_id);
        $this->assertEquals($operator->id, $row->transferred_by);
        $this->assertNull($row->remarks);
        $this->assertNotNull($row->created_at);
    }
}
