<?php

namespace Tests\Feature;

use App\Models\Priority;
use App\Models\QueueTicket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GuardSummaryPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagination_meta_calculations_are_correct()
    {
        $user = User::factory()->create();
        $transaction = Transaction::create(['name' => 'Registration', 'code' => 'REG', 'workflow_order' => 1, 'is_active' => true]);

        for ($i = 1; $i <= 35; $i++) {
            QueueTicket::create([
                'transaction_id' => $transaction->id,
                'priority_id' => null,
                'counter_id' => null,
                'queue_number' => 'REG'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'name' => 'Name '.$i,
                'daily_sequence' => $i,
                'status' => 'waiting',
                'created_at' => now()->subMinutes(35 - $i),
                'updated_at' => now()->subMinutes(35 - $i),
            ]);
        }

        $response = $this->actingAs($user)->getJson(route('queue.guard-summary.data', ['per_page' => 10, 'page' => 2]));

        $response->assertStatus(200);
        $response->assertJsonPath('meta.page', 2);
        $response->assertJsonPath('meta.per_page', 10);
        $response->assertJsonPath('meta.total', 35);
        $response->assertJsonPath('meta.last_page', 4);
        $response->assertJsonPath('meta.from', 11);
        $response->assertJsonPath('meta.to', 20);
        $this->assertCount(10, $response->json('data'));
    }

    public function test_status_filter_narrows_results_without_breaking_pagination()
    {
        $user = User::factory()->create();
        $transaction = Transaction::create(['name' => 'Registration', 'code' => 'REG', 'workflow_order' => 1, 'is_active' => true]);

        for ($i = 1; $i <= 10; $i++) {
            QueueTicket::create([
                'transaction_id' => $transaction->id,
                'priority_id' => null,
                'counter_id' => null,
                'queue_number' => 'REGW'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'name' => 'Waiting '.$i,
                'daily_sequence' => $i,
                'status' => 'waiting',
                'created_at' => now()->subMinutes(20 - $i),
                'updated_at' => now()->subMinutes(20 - $i),
            ]);
        }

        for ($i = 1; $i <= 5; $i++) {
            QueueTicket::create([
                'transaction_id' => $transaction->id,
                'priority_id' => null,
                'counter_id' => null,
                'queue_number' => 'REGS'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'name' => 'Serving '.$i,
                'daily_sequence' => 100 + $i,
                'status' => 'serving',
                'created_at' => now()->subMinutes(10 - $i),
                'updated_at' => now()->subMinutes(10 - $i),
            ]);
        }

        $response = $this->actingAs($user)->getJson(route('queue.guard-summary.data', [
            'per_page' => 10,
            'page' => 1,
            'status' => ['serving'],
        ]));

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 5);
        $response->assertJsonPath('meta.last_page', 1);
        $this->assertCount(5, $response->json('data'));
        foreach ($response->json('data') as $row) {
            $this->assertSame('serving', $row['status']);
        }
    }

    public function test_date_range_filter_includes_only_matching_days()
    {
        $user = User::factory()->create();
        $transaction = Transaction::create(['name' => 'Registration', 'code' => 'REG', 'workflow_order' => 1, 'is_active' => true]);

        Carbon::setTestNow(Carbon::parse('2026-03-05 10:00:00'));
        QueueTicket::create([
            'transaction_id' => $transaction->id,
            'priority_id' => null,
            'counter_id' => null,
            'queue_number' => 'REG0001',
            'name' => 'Day 1',
            'daily_sequence' => 1,
            'status' => 'waiting',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-03-06 11:00:00'));
        QueueTicket::create([
            'transaction_id' => $transaction->id,
            'priority_id' => null,
            'counter_id' => null,
            'queue_number' => 'REG0002',
            'name' => 'Day 2',
            'daily_sequence' => 2,
            'status' => 'waiting',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Carbon::setTestNow();

        $response = $this->actingAs($user)->getJson(route('queue.guard-summary.data', [
            'date_from' => '2026-03-06',
            'date_to' => '2026-03-06',
            'per_page' => 10,
            'page' => 1,
        ]));

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 1);
        $this->assertSame('REG0002', $response->json('data.0.queue_number'));
    }

    public function test_search_filters_across_multiple_fields()
    {
        $user = User::factory()->create();
        $transaction = Transaction::create(['name' => 'Professional Teacher', 'code' => 'PT', 'workflow_order' => 1, 'is_active' => true]);
        $priority = Priority::create(['name' => 'Senior Citizen', 'code' => 'SC', 'priority_level' => 1, 'is_active' => true]);

        QueueTicket::create([
            'transaction_id' => $transaction->id,
            'priority_id' => $priority->id,
            'counter_id' => 7,
            'queue_number' => 'PT0001',
            'name' => 'Alice',
            'daily_sequence' => 1,
            'status' => 'waiting',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $byTransaction = $this->actingAs($user)->getJson(route('queue.guard-summary.data', ['q' => 'Professional']));
        $byTransaction->assertStatus(200);
        $byTransaction->assertJsonPath('meta.total', 1);

        $byPriority = $this->actingAs($user)->getJson(route('queue.guard-summary.data', ['q' => 'Senior']));
        $byPriority->assertStatus(200);
        $byPriority->assertJsonPath('meta.total', 1);

        $byCounter = $this->actingAs($user)->getJson(route('queue.guard-summary.data', ['q' => '7']));
        $byCounter->assertStatus(200);
        $byCounter->assertJsonPath('meta.total', 1);
    }
}
