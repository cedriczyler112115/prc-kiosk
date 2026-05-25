<?php

namespace Tests\Feature;

use App\Models\Counter;
use App\Models\Priority;
use App\Models\QueueTicket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class QueueListPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagination_meta_calculations_are_correct()
    {
        $user = User::factory()->create();
        $transaction = Transaction::create(['name' => 'Registration', 'code' => 'REG', 'workflow_order' => 1, 'is_active' => true]);

        for ($i = 1; $i <= 35; $i++) {
            QueueTicket::create([
                'transaction_id' => $transaction->id,
                'queue_number' => 'REG'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'name' => 'Name '.$i,
                'daily_sequence' => $i,
                'status' => 'waiting',
                'created_at' => now()->subMinutes(35 - $i),
                'updated_at' => now()->subMinutes(35 - $i),
            ]);
        }

        $response = $this->actingAs($user)->getJson(route('queue.list.data', ['per_page' => 10, 'page' => 2]));

        $response->assertStatus(200);
        $response->assertJsonPath('meta.page', 2);
        $response->assertJsonPath('meta.per_page', 10);
        $response->assertJsonPath('meta.total', 35);
        $response->assertJsonPath('meta.last_page', 4);
        $this->assertCount(10, $response->json('data'));
    }

    public function test_status_filter()
    {
        $user = User::factory()->create();
        $transaction = Transaction::create(['name' => 'Registration', 'code' => 'REG', 'workflow_order' => 1, 'is_active' => true]);

        QueueTicket::create([
            'transaction_id' => $transaction->id,
            'queue_number' => 'W001',
            'status' => 'waiting',
            'created_at' => now(),
        ]);
        QueueTicket::create([
            'transaction_id' => $transaction->id,
            'queue_number' => 'S001',
            'status' => 'serving',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson(route('queue.list.data', ['status' => 'serving']));

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 1);
        $this->assertSame('serving', $response->json('data.0.status'));
    }

    public function test_date_filter()
    {
        $user = User::factory()->create();
        $transaction = Transaction::create(['name' => 'Registration', 'code' => 'REG', 'workflow_order' => 1, 'is_active' => true]);

        Carbon::setTestNow(Carbon::parse('2026-03-01 10:00:00'));
        QueueTicket::create([
            'transaction_id' => $transaction->id,
            'queue_number' => 'D001',
            'status' => 'waiting',
            'created_at' => now(),
        ]);
        Carbon::setTestNow(Carbon::parse('2026-03-02 10:00:00'));
        QueueTicket::create([
            'transaction_id' => $transaction->id,
            'queue_number' => 'D002',
            'status' => 'waiting',
            'created_at' => now(),
        ]);
        Carbon::setTestNow();

        $response = $this->actingAs($user)->getJson(route('queue.list.data', ['date' => '2026-03-02']));

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 1);
        $this->assertSame('D002', $response->json('data.0.queue_number'));
    }

    public function test_search_filter()
    {
        $user = User::factory()->create();
        $transaction = Transaction::create(['name' => 'Registration', 'code' => 'REG', 'workflow_order' => 1, 'is_active' => true]);

        QueueTicket::create([
            'transaction_id' => $transaction->id,
            'queue_number' => 'ABC-123',
            'name' => 'John Doe',
            'created_at' => now(),
        ]);
        QueueTicket::create([
            'transaction_id' => $transaction->id,
            'queue_number' => 'XYZ-789',
            'name' => 'Jane Smith',
            'created_at' => now(),
        ]);

        $res1 = $this->actingAs($user)->getJson(route('queue.list.data', ['q' => 'ABC']));
        $res1->assertJsonPath('meta.total', 1);
        $this->assertSame('ABC-123', $res1->json('data.0.queue_number'));

        $res2 = $this->actingAs($user)->getJson(route('queue.list.data', ['q' => 'Jane']));
        $res2->assertJsonPath('meta.total', 1);
        $this->assertSame('XYZ-789', $res2->json('data.0.queue_number'));
    }

    public function test_transaction_priority_counter_filters()
    {
        $user = User::factory()->create();
        $tx1 = Transaction::create(['name' => 'T1', 'code' => 'T1', 'is_active' => true]);
        $tx2 = Transaction::create(['name' => 'T2', 'code' => 'T2', 'is_active' => true]);
        $p1 = Priority::create(['name' => 'P1', 'code' => 'P1', 'priority_level' => 1, 'is_active' => true]);

        // Setup counter manually if needed, or just use ID since we filter by ID
        $c1_id = 101;
        $c2_id = 102;

        QueueTicket::create([
            'transaction_id' => $tx1->id,
            'priority_id' => $p1->id,
            'counter_id' => $c1_id,
            'queue_number' => 'Q1',
            'created_at' => now(),
        ]);
        QueueTicket::create([
            'transaction_id' => $tx2->id,
            'priority_id' => null,
            'counter_id' => $c2_id,
            'queue_number' => 'Q2',
            'created_at' => now(),
        ]);

        // Filter by Transaction
        $this->actingAs($user)->getJson(route('queue.list.data', ['transaction_id' => $tx1->id]))
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.queue_number', 'Q1');

        // Filter by Priority
        $this->actingAs($user)->getJson(route('queue.list.data', ['priority_id' => $p1->id]))
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.queue_number', 'Q1');

        // Filter by Counter
        $this->actingAs($user)->getJson(route('queue.list.data', ['counter_id' => $c1_id]))
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.queue_number', 'Q1');
    }
}
