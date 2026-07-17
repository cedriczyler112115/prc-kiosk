<?php

namespace App\Http\Controllers;

use App\Events\QueueTicketUpdated;
use App\Models\Priority;
use App\Models\QueueTicket;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GuardEntryController extends Controller
{
    public function index(Request $request)
    {
        $transactions = Transaction::query()
            ->leftJoin('users', 'transactions.id', '=', 'users.transaction_id')
            ->where('transactions.is_active', true)
            ->orderBy('transactions.workflow_order')
            ->orderBy('transactions.name')
            ->select([
                'transactions.id',
                'transactions.name',
                'transactions.priority_enabled',
                DB::raw('MIN(users.counter_id) as counter_number'),
            ])
            ->groupBy('transactions.id', 'transactions.name', 'transactions.priority_enabled', 'transactions.workflow_order')
            ->get();

        $priorities = Priority::query()
            ->where('is_active', true)
            ->orderBy('priority_level')
            ->orderBy('name')
            ->get(['id', 'name', 'priority_level']);

        return view('queue.guard-entry', [
            'transactions' => $transactions,
            'priorities' => $priorities,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaction_id' => ['required', 'exists:transactions,id'],
            'special_lane' => ['nullable', 'boolean'],
            'priority_id' => ['nullable', 'exists:priorities,id', 'required_if:special_lane,1'],
            'name' => ['nullable', 'string', 'max:150'],
        ]);

        return DB::transaction(function () use ($validated) {
            $transaction = Transaction::lockForUpdate()->findOrFail($validated['transaction_id']);

            // Lock the table (or just query with lock) to prevent race conditions
            // We'll query for the max daily_sequence for today for this transaction code
            // Actually, the requirement is "transactions.code" + "auto-increment".
            // If the counter resets daily, we should scope by day.
            // Does the counter scope by transaction type? "Example: If transactions.code is "ABC" and it's the 5th transaction of the day... ABC0005"
            // This implies the sequence is per transaction type.

            $today = now()->startOfDay();

            // Get the last sequence number for this transaction today
            // We use lockForUpdate to prevent concurrent reads of the same max value
            $lastTicket = QueueTicket::query()
                ->where('transaction_id', $transaction->id)
                ->where('created_at', '>=', $today)
                ->lockForUpdate()
                ->orderByDesc('daily_sequence')
                ->first();

            $nextSequence = ($lastTicket ? $lastTicket->daily_sequence : 0) + 1;

            // Format: CODE + 4-digit sequence (e.g. ABC0005)
            $queueNumber = $transaction->code.str_pad($nextSequence, 3, '0', STR_PAD_LEFT);

            $ticket = QueueTicket::create([
                'transaction_id' => $transaction->id,
                'priority_id' => $validated['priority_id'] ?? null,
                'counter_id' => null,
                'queue_number' => $queueNumber,
                'name' => $validated['name'] ?? null,
                'daily_sequence' => $nextSequence,
                'status' => 'waiting',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            \DB::table('queue_logs')->insert([
                'queue_id' => $ticket->id,
                'action' => 'created',
                'old_status' => null,
                'new_status' => 'waiting',
                'performed_by' => auth()->id(),
                'remarks' => null,
                'created_at' => now(),
            ]);

            Log::info('Queue ticket created', [
                'ticket_id' => $ticket->id,
                'queue_number' => $ticket->queue_number,
                'transaction_id' => $transaction->id,
                'user_id' => auth()->id(),
            ]);

            QueueTicketUpdated::dispatch($ticket);

            return response()->json([
                'message' => 'Queue ticket created successfully',
                'ticket' => [
                    'queue_number' => $ticket->queue_number,
                    'created_at' => $ticket->created_at->format('m/d/Y h:i A'),
                    'transaction_name' => $transaction->name,
                    'priority_name' => $ticket->priority ? $ticket->priority->name : '',
                    'name' => $ticket->name,
                ],
            ]);
        });
    }

    public function summary()
    {
        $transactions = Transaction::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $priorities = Priority::query()
            ->where('is_active', true)
            ->orderBy('priority_level')
            ->get(['id', 'name']);

        $counters = DB::table('users')
            ->whereNotNull('counter_id')
            ->distinct()
            ->orderBy('counter_id')
            ->pluck('counter_id');

        return view('queue.guard-summary', [
            'transactions' => $transactions,
            'priorities' => $priorities,
            'counters' => $counters,
        ]);
    }

    public function summaryData(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        $allowedPerPage = [10, 25, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $page = max(1, (int) $request->query('page', 1));

        $query = QueueTicket::query()
            ->with(['transaction', 'priority'])
            ->leftJoin('transactions as t', 'queues.transaction_id', '=', 't.id')
            ->leftJoin('priorities as p', 'queues.priority_id', '=', 'p.id')
            ->select('queues.*');

        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        if ($dateFrom !== null && $dateFrom !== '') {
            $query->where('queues.created_at', '>=', Carbon::parse($dateFrom)->startOfDay());
        }
        if ($dateTo !== null && $dateTo !== '') {
            $query->where('queues.created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }
        if (($dateFrom === null || $dateFrom === '') && ($dateTo === null || $dateTo === '') && $request->filled('date')) {
            $query->whereDate('queues.created_at', $request->date);
        }

        $status = $request->query('status');
        if (is_array($status) && count($status) > 0) {
            $query->whereIn('queues.status', $status);
        } elseif (is_string($status) && $status !== '') {
            $query->where('queues.status', $status);
        }

        // Transaction Filter
        if ($request->filled('transaction_id')) {
            $query->where('queues.transaction_id', $request->transaction_id);
        }

        // Priority Filter
        if ($request->filled('priority_id')) {
            $query->where('queues.priority_id', $request->priority_id);
        }

        // Counter Filter
        if ($request->filled('counter_id')) {
            $query->where('queues.counter_id', $request->counter_id);
        }

        // Search Filter (optional but good)
        $search = trim((string) $request->query('q', $request->query('search', '')));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('queues.queue_number', 'like', "%{$search}%")
                    ->orWhere('queues.name', 'like', "%{$search}%")
                    ->orWhere('t.name', 'like', "%{$search}%")
                    ->orWhere('p.name', 'like', "%{$search}%");

                if (trim($search) !== '') {
                    $q->orWhere('queues.counter_id', $search);
                }
            });
        }

        // Sorting: Priority first (lower level = higher priority), then earliest entry
        $query
            ->orderByRaw('CASE WHEN p.priority_level IS NULL THEN 1 ELSE 0 END')
            ->orderBy('p.priority_level', 'asc')
            ->orderBy('queues.created_at', 'asc')
            ->orderBy('queues.id', 'asc');

        $paginator = $query->paginate($perPage, ['queues.*'], 'page', $page);

        return response()->json([
            'data' => $paginator->getCollection()->values(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }
}
