<?php

namespace Tests\Feature;

use App\Models\QueueLog;
use App\Models\QueueTicket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueLogsPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_pagination_meta()
    {
        $user = User::factory()->create();
        $transaction = Transaction::create(['name' => 'Registration', 'code' => 'REG', 'workflow_order' => 1, 'is_active' => true]);

        for ($i = 1; $i <= 35; $i++) {
            $t = QueueTicket::create([
                'transaction_id' => $transaction->id,
                'queue_number' => 'L'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'status' => 'waiting',
                'created_at' => now(),
            ]);

            QueueLog::create([
                'queue_id' => $t->id,
                'action' => 'created',
                'new_status' => 'waiting',
                'created_at' => now(),
            ]);
        }

        $response = $this->actingAs($user)->getJson(route('queue.logs.data', ['per_page' => 10, 'page' => 2]));

        $response->assertStatus(200);
        $response->assertJsonPath('meta.page', 2);
        $response->assertJsonPath('meta.per_page', 10);
        $response->assertJsonPath('meta.total', 35);
        $response->assertJsonPath('meta.last_page', 4);
        $this->assertCount(10, $response->json('data'));
    }

    public function test_logs_date_range_filter()
    {
        $user = User::factory()->create();
        $transaction = Transaction::create(['name' => 'Registration', 'code' => 'REG', 'workflow_order' => 1, 'is_active' => true]);
        $t = QueueTicket::create([
            'transaction_id' => $transaction->id,
            'queue_number' => 'L001',
            'status' => 'waiting',
        ]);

        // Log 1: March 1
        QueueLog::create([
            'queue_id' => $t->id,
            'action' => 'created',
            'created_at' => '2026-03-01 10:00:00',
        ]);
        // Log 2: March 5
        QueueLog::create([
            'queue_id' => $t->id,
            'action' => 'updated',
            'created_at' => '2026-03-05 10:00:00',
        ]);
        // Log 3: March 10
        QueueLog::create([
            'queue_id' => $t->id,
            'action' => 'deleted',
            'created_at' => '2026-03-10 10:00:00',
        ]);

        $res = $this->actingAs($user)->getJson(route('queue.logs.data', [
            'start_date' => '2026-03-02',
            'end_date' => '2026-03-06',
        ]));

        $res->assertJsonPath('meta.total', 1);
        $this->assertSame('updated', $res->json('data.0.action'));
    }

    public function test_logs_search_filter()
    {
        $user = User::factory()->create();
        $transaction = Transaction::create(['name' => 'Registration', 'code' => 'REG', 'workflow_order' => 1, 'is_active' => true]);

        $t1 = QueueTicket::create([
            'transaction_id' => $transaction->id,
            'queue_number' => 'ABC-123',
            'status' => 'waiting',
        ]);
        $t2 = QueueTicket::create([
            'transaction_id' => $transaction->id,
            'queue_number' => 'XYZ-789',
            'status' => 'waiting',
        ]);

        QueueLog::create([
            'queue_id' => $t1->id,
            'action' => 'special_action',
            'remarks' => 'some remark',
        ]);
        QueueLog::create([
            'queue_id' => $t2->id,
            'action' => 'normal_action',
            'remarks' => 'another remark',
        ]);

        // Search by action
        $this->actingAs($user)->getJson(route('queue.logs.data', ['q' => 'special']))
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.action', 'special_action');

        // Search by queue number (via relationship)
        $this->actingAs($user)->getJson(route('queue.logs.data', ['q' => 'ABC']))
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.queue_number', 'ABC-123');
    }

    public function test_logs_status_filter()
    {
        $user = User::factory()->create();
        $transaction = Transaction::create(['name' => 'Registration', 'code' => 'REG', 'workflow_order' => 1, 'is_active' => true]);
        $t = QueueTicket::create(['transaction_id' => $transaction->id, 'queue_number' => 'S1', 'status' => 'waiting']);

        QueueLog::create(['queue_id' => $t->id, 'action' => '1', 'new_status' => 'waiting']);
        QueueLog::create(['queue_id' => $t->id, 'action' => '2', 'new_status' => 'completed']);

        $this->actingAs($user)->getJson(route('queue.logs.data', ['status' => 'completed']))
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.new_status', 'completed');
    }
}
