<?php

namespace Tests\Feature;

use App\Models\Priority;
use App\Models\QueueTicket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GuardEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user for authentication
        $this->user = User::factory()->create();
    }

    public function test_can_create_queue_ticket_without_counter_id()
    {
        $transaction = Transaction::create([
            'name' => 'Engineer',
            'code' => 'ENG',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('queue.guard-entry.store'), [
            'transaction_id' => $transaction->id,
            'name' => 'Jane Doe',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('queues', [
            'transaction_id' => $transaction->id,
            'counter_id' => null,
            'queue_number' => 'ENG001',
        ]);
    }

    public function test_can_view_guard_entry_page()
    {
        $response = $this->actingAs($this->user)->get(route('queue.guard-entry'));
        $response->assertStatus(200);
    }

    public function test_can_create_queue_ticket()
    {
        $transaction = Transaction::create([
            'name' => 'Professional Teacher',
            'code' => 'PT',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('queue.guard-entry.store'), [
            'transaction_id' => $transaction->id,
            'name' => 'Alice',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'ticket' => [
                    'queue_number',
                    'created_at',
                    'transaction_name',
                ],
            ]);

        $this->assertDatabaseHas('queues', [
            'transaction_id' => $transaction->id,
            'queue_number' => 'PT001',
            'daily_sequence' => 1,
        ]);
    }

    public function test_queue_number_increments()
    {
        $transaction = Transaction::create([
            'name' => 'Nurse',
            'code' => 'NUR',
            'is_active' => true,
        ]);

        // First ticket
        $this->actingAs($this->user)->postJson(route('queue.guard-entry.store'), [
            'transaction_id' => $transaction->id,
            'name' => 'Bob',
        ]);

        // Second ticket
        $response = $this->actingAs($this->user)->postJson(route('queue.guard-entry.store'), [
            'transaction_id' => $transaction->id,
            'name' => 'Charlie',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('queues', [
            'transaction_id' => $transaction->id,
            'queue_number' => 'NUR002',
            'daily_sequence' => 2,
        ]);
    }

    public function test_queue_number_resets_daily()
    {
        $transaction = Transaction::create([
            'name' => 'Criminologist',
            'code' => 'CRM',
            'is_active' => true,
        ]);

        // Ticket for today
        Carbon::setTestNow(Carbon::parse('2026-03-05 10:00:00'));
        $this->actingAs($this->user)->postJson(route('queue.guard-entry.store'), [
            'transaction_id' => $transaction->id,
            'name' => 'First Day Name',
        ]);

        $this->assertDatabaseHas('queues', [
            'transaction_id' => $transaction->id,
            'queue_number' => 'CRM001',
            'daily_sequence' => 1,
            'created_at' => '2026-03-05 10:00:00',
        ]);

        // Move to tomorrow
        Carbon::setTestNow(Carbon::parse('2026-03-06 08:00:00'));

        $response = $this->actingAs($this->user)->postJson(route('queue.guard-entry.store'), [
            'transaction_id' => $transaction->id,
            'name' => 'Second Day Name',
        ]);

        $response->assertStatus(200);

        // Should reset to 1
        $this->assertDatabaseHas('queues', [
            'transaction_id' => $transaction->id,
            'queue_number' => 'CRM001',
            'daily_sequence' => 1,
            'created_at' => '2026-03-06 08:00:00',
        ]);
    }

    public function test_queue_number_scoped_by_transaction_code()
    {
        // Actually, the requirement says "transactions.code" + "auto-increment".
        // It's ambiguous whether the auto-increment is global or per transaction code.
        // "If transactions.code is "ABC" and it's the 5th transaction of the day, the queue_number should be "ABC0005""
        // This implies the counter is for "ABC".
        // Let's verify if my implementation does this.
        // In GuardEntryController:
        // $lastTicket = QueueTicket::query()->where('transaction_id', $transaction->id)...
        // Yes, it scopes by transaction_id.

        $tx1 = Transaction::create(['name' => 'T1', 'code' => 'A', 'is_active' => true]);
        $tx2 = Transaction::create(['name' => 'T2', 'code' => 'B', 'is_active' => true]);

        $this->actingAs($this->user)->postJson(route('queue.guard-entry.store'), ['transaction_id' => $tx1->id, 'name' => 'D One']);
        $this->actingAs($this->user)->postJson(route('queue.guard-entry.store'), ['transaction_id' => $tx2->id, 'name' => 'E Two']);

        $this->assertDatabaseHas('queues', ['queue_number' => 'A001']);
        $this->assertDatabaseHas('queues', ['queue_number' => 'B001']);
    }

    public function test_validates_input()
    {
        $response = $this->actingAs($this->user)->postJson(route('queue.guard-entry.store'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['transaction_id']);
    }

    public function test_special_lane_requires_priority()
    {
        $transaction = Transaction::create([
            'name' => 'Engineer',
            'code' => 'ENG',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('queue.guard-entry.store'), [
            'transaction_id' => $transaction->id,
            'special_lane' => 1,
            'priority_id' => null,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['priority_id']);
    }

    public function test_special_lane_allows_print_when_priority_selected()
    {
        $transaction = Transaction::create([
            'name' => 'Engineer',
            'code' => 'ENG',
            'is_active' => true,
        ]);
        $priority = Priority::create([
            'name' => 'Senior Citizen',
            'code' => 'SC',
            'priority_level' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('queue.guard-entry.store'), [
            'transaction_id' => $transaction->id,
            'special_lane' => 1,
            'priority_id' => $priority->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('queues', [
            'transaction_id' => $transaction->id,
            'priority_id' => $priority->id,
            'queue_number' => 'ENG001',
        ]);
    }

    public function test_priority_optional_when_special_lane_is_off()
    {
        $transaction = Transaction::create([
            'name' => 'Engineer',
            'code' => 'ENG',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('queue.guard-entry.store'), [
            'transaction_id' => $transaction->id,
            'special_lane' => 0,
            'priority_id' => null,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('queues', [
            'transaction_id' => $transaction->id,
            'priority_id' => null,
            'queue_number' => 'ENG001',
        ]);
    }
}
