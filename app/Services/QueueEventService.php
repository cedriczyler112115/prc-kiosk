<?php

namespace App\Services;

use App\Models\QueueTicket;
use Illuminate\Support\Facades\Cache;
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
    protected function formatPayload(QueueTicket $ticket, string $type)
    {
        return [
            'id' => $ticket->id,
            'queue_number' => $ticket->queue_number,
            'status' => $ticket->status,
            'counter_id' => $ticket->counter_id,
            'priority_id' => $ticket->priority_id,
            'name' => $ticket->name,
            'transaction_id' => $ticket->transaction_id,
            'updated_at' => $ticket->updated_at ? $ticket->updated_at->toIso8601String() : now()->toIso8601String(),
            'event_type' => $type,
        ];
    }

    /**
     * Store the event in Cache for SSE to pick up.
     * In the future, this is where Laravel Broadcasting or WebSockets would be called.
     */
    protected function broadcastEvent(string $eventName, array $payload)
    {
        $eventData = [
            'id' => uniqid('evt_', true),
            'event' => $eventName,
            'data' => $payload,
            'timestamp' => microtime(true),
        ];

        // Store an array of recent events to prevent missed events in the SSE 1-second polling gap
        $events = Cache::get('recent_queue_events', []);
        $events[] = $eventData;
        
        // Keep only last 50 events
        if (count($events) > 50) {
            array_shift($events);
        }
        
        Cache::put('recent_queue_events', $events, now()->addMinutes(5));
    }
}
