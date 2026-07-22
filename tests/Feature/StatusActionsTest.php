<?php

namespace Tests\Feature;

use App\Models\QueueTicket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_recall_skipped_sets_called_and_assigns_counter()
    {
        $tx = Transaction::create([
            'name' => 'Licensing',
            'code' => 'LIC',
            'workflow_order' => 1,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'transaction_id' => $tx->id,
            'counter_id' => 3,
        ]);
        $this->actingAs($user);

        $t = QueueTicket::create([
            'transaction_id' => $tx->id,
            'queue_number' => 'LIC0001',
            'status' => 'skipped',
            'daily_sequence' => 1,
            'created_at' => now()->subMinutes(5),
        ]);

        $resp = $this->postJson(route('queue.my-counter.recall-skipped'), ['ticket_id' => $t->id]);
        $resp->assertStatus(200)->assertJson(['success' => true]);

        $t->refresh();
        $this->assertSame('called', $t->status);
        $this->assertEquals(3, $t->counter_id);
        $this->assertNotNull($t->called_at);
        // Waiting time must only be set on the first waiting->called transition, not on recall
        $this->assertNull($t->waiting_time_seconds);
        $this->assertEquals($user->id, $t->called_by);
    }

    public function test_restore_cancelled_sets_waiting_and_clears_counter()
    {
        $tx = Transaction::create([
            'name' => 'Registration',
            'code' => 'REG',
            'workflow_order' => 1,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'transaction_id' => $tx->id,
            'counter_id' => 2,
        ]);
        $this->actingAs($user);

        $t = QueueTicket::create([
            'transaction_id' => $tx->id,
            'queue_number' => 'REG0001',
            'status' => 'cancelled',
            'daily_sequence' => 1,
            'counter_id' => 2,
            'called_at' => now()->subMinutes(10),
            'created_at' => now()->subMinutes(15),
        ]);

        $resp = $this->postJson(route('queue.my-counter.restore-cancelled'), ['ticket_id' => $t->id]);
        $resp->assertStatus(200)->assertJson(['success' => true]);

        $t->refresh();
        $this->assertSame('waiting', $t->status);
        $this->assertNull($t->counter_id);
        $this->assertNull($t->called_at);
        $this->assertNull($t->serving_at);
    }

    public function test_actions_require_user_assignment()
    {
        $tx = Transaction::create([
            'name' => 'Assessment',
            'code' => 'ASM',
            'workflow_order' => 1,
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'transaction_id' => null,
            'counter_id' => null,
        ]);
        $this->actingAs($user);

        $t = QueueTicket::create([
            'transaction_id' => $tx->id,
            'queue_number' => 'ASM0001',
            'status' => 'skipped',
            'daily_sequence' => 1,
            'created_at' => now(),
        ]);

        $resp1 = $this->postJson(route('queue.my-counter.recall-skipped'), ['ticket_id' => $t->id]);
        $resp1->assertStatus(403);

        $t->status = 'cancelled';
        $t->save();
        $resp2 = $this->postJson(route('queue.my-counter.restore-cancelled'), ['ticket_id' => $t->id]);
        $resp2->assertStatus(403);

        $this->actingAs(User::factory()->create(['transaction_id' => $tx->id, 'counter_id' => 5]));
        $skipTarget = QueueTicket::create([
            'transaction_id' => $tx->id,
            'queue_number' => 'ASM0002',
            'status' => 'called',
            'daily_sequence' => 2,
            'called_at' => now(),
        ]);
        $skipResp = $this->postJson(route('queue.my-counter.skip'), ['ticket_id' => $skipTarget->id]);
        $skipResp->assertStatus(200)->assertJson(['success' => true]);
        $skipTarget->refresh();
        $this->assertSame('skipped', $skipTarget->status);
        $this->assertNotNull($skipTarget->skipped_by);

        $cancelTarget = QueueTicket::create([
            'transaction_id' => $tx->id,
            'queue_number' => 'ASM0003',
            'status' => 'called',
            'daily_sequence' => 3,
            'called_at' => now(),
        ]);
        $cancelResp = $this->postJson(route('queue.my-counter.cancel'), ['ticket_id' => $cancelTarget->id]);
        $cancelResp->assertStatus(200)->assertJson(['success' => true]);
        $cancelTarget->refresh();
        $this->assertSame('waiting', $cancelTarget->status);
        $this->assertNull($cancelTarget->counter_id);
        $this->assertNull($cancelTarget->called_at);
        $this->assertNull($cancelTarget->serving_at);
        $this->assertNull($cancelTarget->completed_at);
        $this->assertNull($cancelTarget->service_time_seconds);
        $this->assertNull($cancelTarget->waiting_time_seconds);
        $this->assertNull($cancelTarget->called_by);
        $this->assertNull($cancelTarget->serving_by);
        $this->assertNull($cancelTarget->completed_by);
        $this->assertNull($cancelTarget->skipped_by);
        $this->assertNull($cancelTarget->cancelled_by);
    }
}
