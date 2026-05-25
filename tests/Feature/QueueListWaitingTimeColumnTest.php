<?php

namespace Tests\Feature;

use App\Models\QueueTicket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class QueueListWaitingTimeColumnTest extends TestCase
{
    use RefreshDatabase;

    public function test_waiting_time_seconds_populated_only_on_first_call_for_non_transfer()
    {
        $tx = Transaction::create(['name' => 'Reg', 'code' => 'REG', 'is_active' => true]);
        $operator = User::factory()->create(['transaction_id' => $tx->id, 'counter_id' => 1]);
        $this->actingAs($operator);

        $t = QueueTicket::create([
            'transaction_id' => $tx->id,
            'queue_number' => 'REG-001',
            'status' => 'waiting',
            'daily_sequence' => 1,
            'is_transfer' => false,
            'created_at' => now()->subMinutes(3),
        ]);

        $this->postJson(route('queue.my-counter.call'))->assertStatus(200);
        $t->refresh();
        $this->assertNotNull($t->waiting_time_seconds);
        $this->assertGreaterThan(0, $t->waiting_time_seconds);

        $res = $this->getJson(route('queue.list.data'));
        $res->assertOk();
        $row = collect($res->json('data'))->firstWhere('id', $t->id);
        $this->assertSame($t->waiting_time_seconds, $row['waiting_time_seconds']);
    }

    public function test_waiting_time_seconds_not_set_for_transfer_on_call_and_not_shown_in_list()
    {
        $tx = Transaction::create(['name' => 'Reg2', 'code' => 'R2', 'is_active' => true]);
        $operator = User::factory()->create(['transaction_id' => $tx->id, 'counter_id' => 2]);
        $this->actingAs($operator);

        $t = QueueTicket::create([
            'transaction_id' => $tx->id,
            'queue_number' => 'R2-001',
            'status' => 'waiting',
            'daily_sequence' => 1,
            'is_transfer' => true,
            'created_at' => now()->subMinutes(5),
        ]);

        $this->postJson(route('queue.my-counter.call'))->assertStatus(200);
        $t->refresh();
        $this->assertNull($t->waiting_time_seconds);

        $res = $this->getJson(route('queue.list.data'));
        $res->assertOk();
        $row = collect($res->json('data'))->firstWhere('id', $t->id);
        $this->assertNull($row['waiting_time_seconds']);
    }
}

