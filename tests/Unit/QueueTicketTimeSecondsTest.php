<?php

namespace Tests\Unit;

use App\Models\QueueTicket;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class QueueTicketTimeSecondsTest extends TestCase
{
    use RefreshDatabase;

    public function test_effective_waiting_time_seconds_for_waiting_ticket_is_positive_elapsed_seconds(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-06 10:00:00'));
        $tx = Transaction::create(['name' => 'Tx', 'code' => 'TX', 'is_active' => true]);

        $t = QueueTicket::create([
            'transaction_id' => $tx->id,
            'queue_number' => 'TX-001',
            'status' => 'waiting',
            'daily_sequence' => 1,
        ]);
        $t->forceFill(['created_at' => Carbon::parse('2026-03-06 09:55:00')])->save();

        $this->assertSame(300, $t->effectiveWaitingTimeSeconds());
        Carbon::setTestNow();
    }

    public function test_effective_waiting_time_seconds_for_called_ticket_falls_back_to_timestamps(): void
    {
        $tx = Transaction::create(['name' => 'Tx', 'code' => 'TX', 'is_active' => true]);

        $t = QueueTicket::create([
            'transaction_id' => $tx->id,
            'queue_number' => 'TX-002',
            'status' => 'called',
            'daily_sequence' => 2,
            'waiting_time_seconds' => null,
            'called_at' => Carbon::parse('2026-03-06 09:55:00'),
        ]);
        $t->forceFill(['created_at' => Carbon::parse('2026-03-06 09:50:00')])->save();

        $this->assertSame(300, $t->effectiveWaitingTimeSeconds());
    }

    public function test_effective_service_time_seconds_for_serving_ticket_is_positive_elapsed_seconds(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-06 10:00:00'));
        $tx = Transaction::create(['name' => 'Tx', 'code' => 'TX', 'is_active' => true]);

        $t = QueueTicket::create([
            'transaction_id' => $tx->id,
            'queue_number' => 'TX-003',
            'status' => 'serving',
            'daily_sequence' => 3,
            'service_time_seconds' => null,
            'serving_at' => Carbon::parse('2026-03-06 09:59:00'),
        ]);
        $t->forceFill(['created_at' => Carbon::parse('2026-03-06 09:50:00')])->save();

        $this->assertSame(60, $t->effectiveServiceTimeSeconds());
        Carbon::setTestNow();
    }

    public function test_effective_service_time_seconds_for_completed_ticket_falls_back_to_timestamps(): void
    {
        $tx = Transaction::create(['name' => 'Tx', 'code' => 'TX', 'is_active' => true]);

        $t = QueueTicket::create([
            'transaction_id' => $tx->id,
            'queue_number' => 'TX-004',
            'status' => 'completed',
            'daily_sequence' => 4,
            'service_time_seconds' => null,
            'called_at' => Carbon::parse('2026-03-06 09:10:00'),
            'serving_at' => Carbon::parse('2026-03-06 09:12:00'),
            'completed_at' => Carbon::parse('2026-03-06 09:15:00'),
        ]);
        $t->forceFill(['created_at' => Carbon::parse('2026-03-06 09:00:00')])->save();

        $this->assertSame(180, $t->effectiveServiceTimeSeconds());
    }

    public function test_effective_service_time_seconds_is_null_for_non_serving_and_non_completed_statuses(): void
    {
        $tx = Transaction::create(['name' => 'Tx', 'code' => 'TX', 'is_active' => true]);

        $t = QueueTicket::create([
            'transaction_id' => $tx->id,
            'queue_number' => 'TX-005',
            'status' => 'waiting',
            'daily_sequence' => 5,
        ]);
        $t->forceFill(['created_at' => Carbon::parse('2026-03-06 09:50:00')])->save();

        $this->assertNull($t->effectiveServiceTimeSeconds());
    }

    public function test_sanitize_positive_seconds_converts_invalid_or_non_positive_values_to_fallback(): void
    {
        $this->assertSame(1, QueueTicket::sanitizePositiveSeconds(null));
        $this->assertSame(1, QueueTicket::sanitizePositiveSeconds(''));
        $this->assertSame(1, QueueTicket::sanitizePositiveSeconds('abc'));
        $this->assertSame(1, QueueTicket::sanitizePositiveSeconds(0));
        $this->assertSame(1, QueueTicket::sanitizePositiveSeconds(-5));
        $this->assertSame(10, QueueTicket::sanitizePositiveSeconds('10'));
    }
}
