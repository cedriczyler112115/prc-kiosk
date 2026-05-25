<?php

use App\Models\QueueTicket;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        QueueTicket::query()
            ->where(function ($q) {
                $q->whereNull('waiting_time_seconds')
                    ->orWhere('waiting_time_seconds', '<=', 0)
                    ->orWhereNull('service_time_seconds')
                    ->orWhere('service_time_seconds', '<=', 0);
            })
            ->orderBy('id')
            ->chunkById(200, function ($tickets) {
                foreach ($tickets as $ticket) {
                    $updates = [];

                    if (in_array($ticket->status, ['called', 'serving', 'completed'], true)
                        && ($ticket->waiting_time_seconds === null || $ticket->waiting_time_seconds <= 0)
                        && $ticket->called_at
                    ) {
                        $updates['waiting_time_seconds'] = QueueTicket::diffSecondsPositive($ticket->created_at, $ticket->called_at);
                    }

                    if ($ticket->status === 'completed'
                        && ($ticket->service_time_seconds === null || $ticket->service_time_seconds <= 0)
                        && $ticket->completed_at
                    ) {
                        $start = $ticket->serving_at ?: ($ticket->called_at ?: ($ticket->created_at ?: $ticket->completed_at));
                        $updates['service_time_seconds'] = QueueTicket::diffSecondsPositive($start, $ticket->completed_at);
                    }

                    if ($updates) {
                        QueueTicket::whereKey($ticket->id)->update($updates);
                    }
                }
            });
    }

    public function down(): void {}
};
