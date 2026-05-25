<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class QueueTicket extends Model
{
    protected $table = 'queues';

    protected $fillable = [
        'transaction_id',
        'priority_id',
        'counter_id',
        'queue_number',
        'name',
        'daily_sequence',
        'status',
        'called_at',
        'serving_at',
        'completed_at',
        'called_by',
        'serving_by',
        'completed_by',
        'skipped_by',
        'cancelled_by',
        'waiting_time_seconds',
        'service_time_seconds',
        'is_transfer',
        'transfer_service_started_at',
        'transfer_service_completed_at',
        'transfer_service_time_seconds',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'called_at' => 'datetime',
        'serving_at' => 'datetime',
        'completed_at' => 'datetime',
        'transfer_service_started_at' => 'datetime',
        'transfer_service_completed_at' => 'datetime',
        'transaction_id' => 'integer',
        'priority_id' => 'integer',
        'counter_id' => 'integer',
        'daily_sequence' => 'integer',
        'called_by' => 'integer',
        'serving_by' => 'integer',
        'completed_by' => 'integer',
        'skipped_by' => 'integer',
        'cancelled_by' => 'integer',
        'is_transfer' => 'boolean',
        'waiting_time_seconds' => 'integer',
        'service_time_seconds' => 'integer',
        'transfer_service_time_seconds' => 'integer',
    ];

    public static function sanitizePositiveSeconds(mixed $value, int $fallback = 1): int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : $fallback;
        }

        if (is_float($value)) {
            $n = (int) floor($value);

            return $n > 0 ? $n : $fallback;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return $fallback;
            }
            if (! preg_match('/^-?\d+$/', $trimmed)) {
                return $fallback;
            }

            $n = (int) $trimmed;

            return $n > 0 ? $n : $fallback;
        }

        return $fallback;
    }

    public static function diffSecondsPositive(?Carbon $start, ?Carbon $end, int $fallback = 1): int
    {
        if (! $start || ! $end) {
            return $fallback;
        }

        $delta = $start->diffInSeconds($end, false);

        if ($delta < 0) {
            return $fallback;
        }

        return max(1, (int) $delta);
    }

    public function effectiveWaitingTimeSeconds(?Carbon $asOf = null): int
    {
        $now = $asOf ?: now();

        if ($this->status === 'waiting') {
            return self::diffSecondsPositive($this->created_at, $now);
        }

        $raw = $this->getRawOriginal('waiting_time_seconds');
        if (is_numeric($raw) && (int) $raw > 0) {
            return (int) $raw;
        }

        if ($this->called_at) {
            return self::diffSecondsPositive($this->created_at, $this->called_at);
        }

        return self::diffSecondsPositive($this->created_at, $now);
    }

    public function effectiveServiceTimeSeconds(?Carbon $asOf = null): ?int
    {
        $now = $asOf ?: now();

        if ($this->status !== 'serving' && $this->status !== 'completed') {
            return null;
        }

        $raw = $this->getRawOriginal('service_time_seconds');
        if (is_numeric($raw) && (int) $raw > 0) {
            return (int) $raw;
        }

        if ($this->status === 'serving') {
            if (! $this->serving_at) {
                return null;
            }

            return self::diffSecondsPositive($this->serving_at, $now);
        }

        if (! $this->completed_at) {
            return null;
        }

        $start = $this->serving_at ?: ($this->called_at ?: ($this->created_at ?: $this->completed_at));

        return self::diffSecondsPositive($start, $this->completed_at);
    }

    public function effectiveTransferServiceSeconds(): ?int
    {
        if (! $this->is_transfer) {
            return null;
        }

        if ($this->transfer_service_started_at && $this->transfer_service_completed_at) {
            return self::diffSecondsPositive($this->transfer_service_started_at, $this->transfer_service_completed_at);
        }

        $raw = $this->getRawOriginal('transfer_service_time_seconds');
        if (is_numeric($raw) && (int) $raw > 0) {
            return (int) $raw;
        }

        return null;
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    public function calledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'called_by');
    }

    public function servingByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'serving_by');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
