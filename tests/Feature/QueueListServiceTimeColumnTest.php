<?php

namespace Tests\Feature;

use App\Models\QueueTicket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class QueueListServiceTimeColumnTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_time_column_uses_transfer_duration_for_transferred_items()
    {
        Carbon::setTestNow(Carbon::parse('2026-03-06 12:00:00'));
        $user = User::factory()->create();
        $this->actingAs($user);
        $tx = Transaction::create(['name' => 'Tx', 'code' => 'TX', 'is_active' => true]);

        $t = QueueTicket::create([
            'transaction_id' => $tx->id,
            'queue_number' => 'TX-T-001',
            'status' => 'completed',
            'daily_sequence' => 1,
            'is_transfer' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $t->transfer_service_started_at = Carbon::parse('2026-03-06 10:00:00');
        $t->transfer_service_completed_at = Carbon::parse('2026-03-06 10:03:20'); // 200s
        $t->save();

        $resp = $this->getJson(route('queue.list.data'));
        $resp->assertOk();
        $data = $resp->json('data');
        $this->assertNotEmpty($data);
        $row = collect($data)->firstWhere('id', $t->id);
        $this->assertSame(200, $row['service_time_seconds']);

        Carbon::setTestNow();
    }

    public function test_service_time_column_uses_normal_service_time_for_non_transfers()
    {
        Carbon::setTestNow(Carbon::parse('2026-03-06 12:00:00'));
        $user = User::factory()->create();
        $this->actingAs($user);
        $tx = Transaction::create(['name' => 'Tx2', 'code' => 'T2', 'is_active' => true]);

        $t = QueueTicket::create([
            'transaction_id' => $tx->id,
            'queue_number' => 'TX-N-001',
            'status' => 'completed',
            'daily_sequence' => 2,
            'is_transfer' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $t->serving_at = Carbon::parse('2026-03-06 11:00:00');
        $t->completed_at = Carbon::parse('2026-03-06 11:05:00'); // 300s
        $t->save();

        $resp = $this->getJson(route('queue.list.data'));
        $resp->assertOk();
        $data = $resp->json('data');
        $row = collect($data)->firstWhere('id', $t->id);
        $this->assertSame(300, $row['service_time_seconds']);

        Carbon::setTestNow();
    }
}

