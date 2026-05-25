<?php

namespace Tests\Feature;

use App\Models\Priority;
use App\Models\QueueTicket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_tickets_are_ordered_by_priority()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Setup Transaction Type
        $transaction = Transaction::create([
            'name' => 'Registration',
            'code' => 'REG',
            'workflow_order' => 1,
            'is_active' => true,
        ]);

        // Setup Priorities
        $highPriority = Priority::create([
            'name' => 'Senior Citizen',
            'code' => 'SC',
            'priority_level' => 1,
            'is_active' => true,
        ]);

        $lowPriority = Priority::create([
            'name' => 'Regular',
            'code' => 'REG',
            'priority_level' => 10,
            'is_active' => true,
        ]);

        // Create Tickets
        // 1. Regular ticket (created first) - ID 1
        $ticket1 = QueueTicket::create([
            'transaction_id' => $transaction->id,
            'priority_id' => $lowPriority->id,
            'queue_number' => 'REG-001',
            'status' => 'waiting',
            'daily_sequence' => 1,
            'created_at' => now()->subMinutes(10),
        ]);

        // 2. No priority ticket (created second) - ID 2
        $ticket2 = QueueTicket::create([
            'transaction_id' => $transaction->id,
            'priority_id' => null,
            'queue_number' => 'REG-002',
            'status' => 'waiting',
            'daily_sequence' => 2,
            'created_at' => now()->subMinutes(9),
        ]);

        // 3. High priority ticket (created third) - ID 3
        $ticket3 = QueueTicket::create([
            'transaction_id' => $transaction->id,
            'priority_id' => $highPriority->id,
            'queue_number' => 'REG-003',
            'status' => 'waiting',
            'daily_sequence' => 3,
            'created_at' => now()->subMinutes(8),
        ]);

        // 4. Another High priority ticket (created fourth) - ID 4
        $ticket4 = QueueTicket::create([
            'transaction_id' => $transaction->id,
            'priority_id' => $highPriority->id,
            'queue_number' => 'REG-004',
            'status' => 'waiting',
            'daily_sequence' => 4,
            'created_at' => now()->subMinutes(7),
        ]);

        // Expected Order:
        // 1. High Priority (Level 1) - Ticket 3 (ID 3)
        // 2. High Priority (Level 1) - Ticket 4 (ID 4)
        // 3. Low Priority (Level 10) - Ticket 1 (ID 1)
        // 4. No Priority (Null) - Ticket 2 (ID 2)

        // Hit the API endpoint
        $response = $this->getJson('/live-queue-board/data');

        $response->assertStatus(200);

        $transactions = $response->json('transactions');
        $this->assertCount(1, $transactions);

        $nextInLine = $transactions[0]['next_in_line'];

        // Assert order matches expected priority-based order and structure
        $expected = [
            ['number' => 'REG-003', 'is_priority' => true],
            ['number' => 'REG-004', 'is_priority' => true],
            ['number' => 'REG-001', 'is_priority' => true], // Wait, REG-001 has priority 'Regular' (level 10), so priority_id is NOT null
            ['number' => 'REG-002', 'is_priority' => false], // REG-002 has priority null
        ];

        $this->assertEquals($expected, $nextInLine);
    }
}
