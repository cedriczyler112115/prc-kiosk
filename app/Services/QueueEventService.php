<?php

namespace App\Services;

use App\Http\Controllers\CounterController;
use App\Http\Controllers\QueueBoardController;
use App\Models\QueueTicket;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueueEventService
{
    /**
     * Handle the created event for a QueueTicket.
     */
    public function handleCreated(QueueTicket $ticket)
    {
        $payload = $this->formatPayload($ticket, 'created');
        $this->broadcastEvent('queue_created', $payload);
    }

    /**
     * Handle the updated event for a QueueTicket.
     */
    public function handleUpdated(QueueTicket $ticket)
    {
        $payload = $this->formatPayload($ticket, 'updated');
        $this->broadcastEvent('queue_updated', $payload);
    }

    /**
     * Handle explicit re-announce requests.
     */
    public function handleReannounce(QueueTicket $ticket)
    {
        $payload = $this->formatPayload($ticket, 'reannounce');
        $this->broadcastEvent('queue_updated', $payload);
    }

    /**
     * Format the queue ticket data into a structured payload.
     */
    protected function formatPayload(QueueTicket $ticket, string $type): array
    {
        return [
            'id'             => $ticket->id,
            'queue_number'   => $ticket->queue_number,
            'status'         => $ticket->status,
            'counter_id'     => $ticket->counter_id,
            'priority_id'    => $ticket->priority_id,
            'name'           => $ticket->name,
            'transaction_id' => $ticket->transaction_id,
            'updated_at'     => $ticket->updated_at ? $ticket->updated_at->toIso8601String() : now()->toIso8601String(),
            'event_type'     => $type,
        ];
    }

    /**
     * Publish the event to the SSE cache ring-buffer and simultaneously bust
     * every relevant server-side cache so stale data is never served:
     *
     *  1. board_data       — the live queue board shared by all TV displays
     *  2. counter_data_*   — the per-counter data used by each agent's "My Counter" page
     *
     * Only the counter whose counter_id / transaction_id matches the event
     * gets its cache busted, leaving untouched counters' caches intact.
     */
    protected function broadcastEvent(string $eventName, array $payload): void
    {
        $eventData = [
            'id'        => uniqid('evt_', true),
            'event'     => $eventName,
            'data'      => $payload,
            'timestamp' => microtime(true),
        ];

        // ── SSE ring-buffer ──────────────────────────────────────────────────
        $events   = Cache::get('recent_queue_events', []);
        $events[] = $eventData;

        if (count($events) > 50) {
            array_shift($events);
        }

        Cache::put('recent_queue_events', $events, now()->addMinutes(5));

        // ── Bust board cache ─────────────────────────────────────────────────
        // Every queue event changes what the live board should show, so the
        // shared board snapshot must be rebuilt on the next request.
        Cache::forget(QueueBoardController::BOARD_DATA_CACHE_KEY);

        // ── Bust per-counter cache ───────────────────────────────────────────
        // Find every active counter assigned to the ticket's transaction and
        // clear their cached snapshot so the next myCounterData() call is fresh.
        // We resolve active counters directly from DB — this is a single cheap
        // query that only runs when a real event fires (not on every poll).
        try {
            $transactionId = $payload['counter_id'] ?? null;
            $ticketCounterId = $payload['counter_id'] ?? null;
            $ticketTransactionId = $payload['transaction_id'] ?? null;

            if ($ticketTransactionId !== null) {
                // Clear cache for the specific counter that owns this ticket.
                if ($ticketCounterId !== null) {
                    Cache::forget(
                        CounterController::counterDataCacheKey($ticketCounterId, $ticketTransactionId)
                    );
                }

                // Also clear cache for all other counters assigned to the same
                // transaction (they see the same waiting list and need fresh data).
                $counterIds = DB::table('users')
                    ->where('transaction_id', $ticketTransactionId)
                    ->whereNotNull('counter_id')
                    ->pluck('counter_id');

                foreach ($counterIds as $cid) {
                    Cache::forget(
                        CounterController::counterDataCacheKey($cid, $ticketTransactionId)
                    );
                }
            }
        } catch (\Throwable $e) {
            // Non-fatal: the 3-second TTL will handle stale data if this fails.
            Log::warning('queue_event_cache_bust_failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
        }
    }
}
