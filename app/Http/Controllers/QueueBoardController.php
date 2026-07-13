<?php

namespace App\Http\Controllers;

use App\Models\QueueTicket;

class QueueBoardController extends Controller
{
    public static function applyPriorityLaneSuffix(string $text, mixed $priorityId): string
    {
        $hasPriority = !is_null($priorityId);
        if (is_string($priorityId) && trim($priorityId) === '') {
            $hasPriority = false;
        }

        $normalized = trim($text);
        if (!$hasPriority) {
            return $normalized;
        }

        if (preg_match('/priority lane\.?$/i', $normalized) === 1) {
            return $normalized;
        }

        $suffix = 'Priority lane';
        if (str_ends_with($normalized, '.')) {
            return $normalized . ' ' . $suffix . '.';
        }

        return $normalized . ' ' . $suffix;
    }

    private static function buildAnnouncement(?string $queueNumber, mixed $counterId, mixed $priorityId): string
    {
        $q = trim((string) ($queueNumber ?? ''));
        $counterNumber = $counterId ? (string) $counterId : '?';
        $base = "Queue number {$q}, please proceed to counter number {$counterNumber}";

        return self::applyPriorityLaneSuffix($base, $priorityId);
    }

    public function index()
    {
        return view('queue.board');
    }

    public function data()
    {
        $transactions = \App\Models\Transaction::where('is_active', true)
            ->orderBy('workflow_order')
            ->orderBy('name')
            ->get()
            ->map(function ($transaction) {
                // Get ALL serving/called tickets for this transaction
                $serving = QueueTicket::query()
                    ->where('transaction_id', $transaction->id)
                    ->whereIn('status', ['called', 'serving'])
                    ->whereDate('created_at', today())
                    ->orderByDesc('called_at')
                    ->get()
                    ->map(function ($ticket) {
                        return [
                            'status' => $ticket->status,
                            'queue_number' => $ticket->queue_number,
                            'priority_id' => $ticket->priority_id,
                            'counter_name' => $ticket->counter_id ? ('Counter ' . $ticket->counter_id) : 'Counter ?',
                            'announcement' => self::buildAnnouncement($ticket->queue_number, $ticket->counter_id, $ticket->priority_id),
                            'called_at' => $ticket->called_at ? $ticket->called_at->toISOString() : null,
                            'is_blinking' => $ticket->status === 'called',
                            'is_priority' => !is_null($ticket->priority_id),
                        ];
                    });

                $totalWaitingCount = QueueTicket::where('transaction_id', $transaction->id)
                    ->where('status', 'waiting')
                    ->whereDate('created_at', today())
                    ->count();

                // Get the next 5 waiting tickets for this transaction
                $waiting = QueueTicket::select('queues.queue_number', 'queues.priority_id')
                    ->leftJoin('priorities', 'queues.priority_id', '=', 'priorities.id')
                    ->where('queues.transaction_id', $transaction->id)
                    ->where('queues.status', 'waiting')
                    ->whereDate('queues.created_at', today())
                    ->orderByRaw('CASE WHEN priorities.priority_level IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('priorities.priority_level', 'asc')
                    ->orderBy('queues.is_transfer', 'desc')
                    ->orderBy('queues.transfer_priority_score', 'desc')
                    ->orderBy('queues.id', 'asc')
                    ->limit(5)
                    ->get()
                    ->map(function ($ticket) {
                    return [
                        'number' => $ticket->queue_number,
                        'is_priority' => !is_null($ticket->priority_id),
                    ];
                });

                return [
                    'id' => $transaction->id,
                    'name' => $transaction->name,
                    'serving' => $serving,
                    'next_in_line' => $waiting,
                    'total_waiting' => $totalWaitingCount,
                ];
            });

        $reannounces = \DB::table('queue_logs')
            ->join('queues', 'queue_logs.queue_id', '=', 'queues.id')
            ->where('queue_logs.action', 'reannounce')
            ->whereDate('queue_logs.created_at', today())
            ->orderByDesc('queue_logs.id')
            ->limit(1)
            ->get([
                'queue_logs.id as id',
                'queues.transaction_id as transaction_id',
                'queues.queue_number as queue_number',
                'queues.counter_id as counter_id',
                'queues.priority_id as priority_id',
                'queue_logs.created_at as created_at',
            ])
            ->map(function ($r) {
                $announcement = self::buildAnnouncement((string) $r->queue_number, $r->counter_id, $r->priority_id);

                return [
                    'id' => (int) $r->id,
                    'transaction_id' => (int) $r->transaction_id,
                    'queue_number' => (string) $r->queue_number,
                    'priority_id' => $r->priority_id,
                    'counter_name' => $r->counter_id ? ('Counter ' . (string) $r->counter_id) : 'Counter ?',
                    'announcement' => $announcement,
                    'created_at' => \Illuminate\Support\Carbon::parse($r->created_at)->toISOString(),
                ];
            });

        return response()->json([
            'transactions' => $transactions,
            'reannouncements' => $reannounces,
        ]);
    }
}
