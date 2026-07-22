<?php

namespace App\Http\Controllers;

use App\Models\AccessLevel;
use App\Models\QueueTicket;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransferPriorityMapper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CounterController extends Controller
{
    /**
     * How long (seconds) the per-counter data response is cached.
     * Keeps multiple rapid SSE events from hammering the DB with identical queries.
     */
    private const COUNTER_DATA_TTL = 3;

    /**
     * File-cache key for a specific counter's data snapshot.
     * Used by QueueEventService to bust the cache the instant a relevant event fires.
     */
    public static function counterDataCacheKey(mixed $counterId, mixed $transactionId): string
    {
        return 'counter_data_' . $counterId . '_' . $transactionId;
    }
    public function index(Request $request)
    {
        $transactions = Transaction::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $accessLevels = AccessLevel::query()
            ->orderBy('hierarchy')
            ->get();

        return view('libraries.windows.index', [
            'transactions' => $transactions,
            'users' => $users,
            'accessLevels' => $accessLevels,
        ]);
    }

    public function data(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        $allowedPerPage = [10, 25, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('q', ''));

        $query = User::query()
            ->with(['transaction:id,name', 'accessLevelLibrary'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('counter_id', 'like', "%{$search}%");
                });
            })
            ->orderBy('name');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $data = $paginator->getCollection()->map(function (User $u) {
            return [
                'id' => $u->id,
                'transaction_id' => $u->transaction_id,
                'transaction_name' => $u->transaction?->name,
                'name' => $u->name,
                'email' => $u->email,
                'counter_number' => $u->counter_id,
                'access_level_id' => $u->access_level_id,
                'access_level_name' => $u->accessLevelLibrary?->name ?? 'N/A',
                'created_at' => $u->created_at?->format('Y-m-d H:i'),
                'updated_at' => $u->updated_at?->format('Y-m-d H:i'),
            ];
        })->values();

        return response()->json([
            'data' => $data,
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'transaction_id' => ['nullable', 'integer', 'exists:transactions,id'],
            'counter_number' => ['nullable', 'string', 'max:50'],
            'access_level_id' => ['required', 'integer', 'exists:access_level_library,id'],
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $user = User::query()->lockForUpdate()->findOrFail($validated['user_id']);
                $user->transaction_id = $validated['transaction_id'];
                $user->counter_id = $validated['counter_number'];
                $user->access_level_id = $validated['access_level_id'];
                $user->save();
            });
        } catch (\Throwable $e) {
            return redirect()->route('libraries.windows')->with('error', 'Failed to update user assignment.');
        }

        return redirect()->route('libraries.windows')->with('success', 'User assignment updated.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'transaction_id' => ['nullable', 'integer', 'exists:transactions,id'],
            'counter_number' => ['nullable', 'string', 'max:50'],
            'access_level_id' => ['required', 'integer', 'exists:access_level_library,id'],
        ]);

        try {
            DB::transaction(function () use ($user, $validated) {
                $user->transaction_id = $validated['transaction_id'];
                $user->counter_id = $validated['counter_number'];
                $user->access_level_id = $validated['access_level_id'];
                $user->save();
            });
        } catch (\Throwable $e) {
            return redirect()->route('libraries.windows')->with('error', 'Failed to update user assignment.');
        }

        return redirect()->route('libraries.windows')->with('success', 'User assignment updated.');
    }

    public function destroy(User $user)
    {
        try {
            DB::transaction(function () use ($user) {
                $user->transaction_id = null;
                $user->counter_id = null;
                $user->save();
            });
        } catch (\Throwable $e) {
            return redirect()->route('libraries.windows')->with('error', 'Failed to unassign window.');
        }

        return redirect()->route('libraries.windows')->with('success', 'Window unassigned.');
    }

    public function myCounter()
    {
        $user = Auth::user();
        if (! $user->transaction_id) {
            return redirect()->route('dashboard')->with('error', 'You are not assigned to any transaction.');
        }
        if (! $user->counter_id) {
            return redirect()->route('dashboard')->with('error', 'You are not assigned to any counter.');
        }

        $transactions = Transaction::where('is_active', true)
            ->where('id', '!=', $user->transaction_id)
            ->get(['id', 'name']);

        return view('queue.my-counter', [
            'user' => $user,
            'transaction' => $user->transaction,
            'transactions' => $transactions,
        ]);
    }

    public function myCounterAppMode()
    {
        $user = Auth::user();
        if (! $user->transaction_id) {
            return redirect()->route('dashboard')->with('error', 'You are not assigned to any transaction.');
        }
        if (! $user->counter_id) {
            return redirect()->route('dashboard')->with('error', 'You are not assigned to any counter.');
        }

        $transactions = Transaction::where('is_active', true)
            ->where('id', '!=', $user->transaction_id)
            ->get(['id', 'name']);

        return view('queue.my-counter-app', [
            'user'         => $user,
            'transaction'  => $user->transaction,
            'transactions' => $transactions,
        ]);
    }

    public function myCounterData()
    {
        $user = Auth::user();
        if (! $user->transaction_id || ! $user->counter_id) {
            return response()->json(['error' => 'No transaction or counter assigned'], 403);
        }

        // Cache the response per (counter_id, transaction_id) for COUNTER_DATA_TTL seconds.
        // This collapses burst SSE events (e.g., multiple rapid calls) into a single DB
        // round-trip per counter. The cache is busted by QueueEventService when an event
        // affecting this counter fires, so data stays accurate within one polling cycle.
        $cacheKey = self::counterDataCacheKey($user->counter_id, $user->transaction_id);

        $data = Cache::remember($cacheKey, self::COUNTER_DATA_TTL, function () use ($user) {
            $currentTicket = QueueTicket::with(['transaction', 'priority'])
                ->where('counter_id', $user->counter_id)
                ->whereIn('status', ['called', 'serving'])
                ->whereDate('created_at', today())
                ->orderBy('id', 'desc')
                ->first();

            $waitingTickets = QueueTicket::select('queues.*')
                ->leftJoin('priorities', 'queues.priority_id', '=', 'priorities.id')
                ->where('queues.transaction_id', $user->transaction_id)
                ->where('queues.status', 'waiting')
                ->whereDate('queues.created_at', today())
                ->orderByRaw('CASE WHEN priorities.priority_level IS NULL THEN 1 ELSE 0 END')
                ->orderBy('priorities.priority_level', 'asc')
                ->orderBy('queues.is_transfer', 'desc')
                ->orderBy('queues.transfer_priority_score', 'desc')
                ->orderBy('queues.transfer_classified_at', 'asc')
                ->orderBy('queues.id', 'asc')
                ->with(['priority', 'transaction'])
                ->limit(10)
                ->get();

            $skippedTickets = QueueTicket::query()
                ->where('transaction_id', $user->transaction_id)
                ->where('status', 'skipped')
                ->whereDate('created_at', today())
                ->orderBy('id', 'desc')
                //->limit(10)
                ->get(['id', 'queue_number', 'created_at']);

            $cancelledTickets = QueueTicket::query()
                ->where('transaction_id', $user->transaction_id)
                ->where('status', 'cancelled')
                ->whereDate('created_at', today())
                ->orderBy('id', 'desc')
                ->limit(10)
                ->get(['id', 'queue_number', 'created_at']);

            return [
                'current'   => $currentTicket,
                'waiting'   => $waitingTickets,
                'skipped'   => $skippedTickets,
                'cancelled' => $cancelledTickets,
            ];
        });

        return response()->json($data);
    }

    public function callNext(Request $request)
    {
        $user = Auth::user();
        if (! $user->transaction_id || ! $user->counter_id) {
            return response()->json(['error' => 'User not assigned to transaction or counter'], 403);
        }
        $active = QueueTicket::where('counter_id', $user->counter_id)
            ->whereIn('status', ['called', 'serving'])
            ->whereDate('created_at', today())
            ->exists();
        if ($active) {
            return response()->json(['error' => 'You have an active ticket. Please complete or transfer it first.'], 400);
        }
        $result = DB::transaction(function () use ($user) {
            $nextTicket = QueueTicket::select('queues.*')
                ->leftJoin('priorities', 'queues.priority_id', '=', 'priorities.id')
                ->where('queues.transaction_id', $user->transaction_id)
                ->where('queues.status', 'waiting')
                ->whereDate('queues.created_at', today())
                ->orderByRaw('CASE WHEN priorities.priority_level IS NULL THEN 1 ELSE 0 END')
                ->orderBy('priorities.priority_level', 'asc')
                ->orderBy('queues.is_transfer', 'desc')
                ->orderBy('queues.transfer_priority_score', 'desc')
                ->orderBy('queues.transfer_classified_at', 'asc')
                ->orderBy('queues.id', 'asc')
                ->lockForUpdate()
                ->first();
            if (! $nextTicket) {
                return null;
            }
            $old = $nextTicket->status;
            $nextTicket->status = 'called';
            $nextTicket->counter_id = $user->counter_id;
            $nextTicket->called_at = now();
            if ($old === 'waiting' && ! $nextTicket->is_transfer && $nextTicket->waiting_time_seconds === null) {
                $nextTicket->waiting_time_seconds = QueueTicket::diffSecondsPositive($nextTicket->created_at, $nextTicket->called_at);
            }
            if ($old === 'waiting' && $nextTicket->is_transfer && ! $nextTicket->transfer_service_started_at) {
                $nextTicket->transfer_service_started_at = $nextTicket->called_at;
            }
            $nextTicket->called_by = $user->id;
            $nextTicket->save();
            if ($nextTicket->is_transfer && $old === 'waiting') {
                \DB::table('queue_logs')->insert([
                    'queue_id' => $nextTicket->id,
                    'action' => 'transfer_service_started',
                    'old_status' => $old,
                    'new_status' => 'called',
                    'performed_by' => $user->id,
                    'remarks' => json_encode([
                        'started_at' => $nextTicket->transfer_service_started_at?->toDateTimeString(),
                    ], JSON_UNESCAPED_SLASHES),
                    'created_at' => now(),
                ]);
            }
            \DB::table('queue_logs')->insert([
                'queue_id' => $nextTicket->id,
                'action' => 'called',
                'old_status' => $old,
                'new_status' => 'called',
                'performed_by' => $user->id,
                'remarks' => null,
                'created_at' => now(),
            ]);

            return $nextTicket;
        });
        if (! $result) {
            return response()->json(['error' => 'No tickets in waiting list'], 404);
        }

        return response()->json(['success' => true, 'ticket' => $result]);
    }

    public function serve(Request $request)
    {
        $request->validate(['ticket_id' => 'required|exists:queues,id']);
        $userId = Auth::id();
        $ok = DB::transaction(function () use ($request, $userId) {
            $ticket = QueueTicket::lockForUpdate()->find($request->ticket_id);
            if ($ticket->status !== 'called') {
                return false;
            }
            $old = $ticket->status;
            $ticket->status = 'serving';
            $ticket->serving_at = now();
            $ticket->serving_by = $userId;
            $ticket->save();
            \DB::table('queue_logs')->insert([
                'queue_id' => $ticket->id,
                'action' => 'serving',
                'old_status' => $old,
                'new_status' => 'serving',
                'performed_by' => $userId,
                'remarks' => null,
                'created_at' => now(),
            ]);

            return true;
        });
        if (! $ok) {
            return response()->json(['error' => 'Ticket must be called first'], 400);
        }

        return response()->json(['success' => true]);
    }

    public function complete(Request $request)
    {
        $request->validate(['ticket_id' => 'required|exists:queues,id']);
        $userId = Auth::id();
        $ok = DB::transaction(function () use ($request, $userId) {
            $ticket = QueueTicket::lockForUpdate()->find($request->ticket_id);
            if ($ticket->status !== 'serving') {
                return false;
            }
            $old = $ticket->status;
            $ticket->status = 'completed';
            $ticket->completed_at = now();
            $start = $ticket->serving_at ?: ($ticket->called_at ?: ($ticket->created_at ?: $ticket->completed_at));
            $ticket->service_time_seconds = QueueTicket::diffSecondsPositive($start, $ticket->completed_at);
            if ($ticket->is_transfer && $ticket->transfer_service_time_seconds === null) {
                $tsStart = $ticket->transfer_service_started_at ?: $ticket->serving_at ?: $start;
                $ticket->transfer_service_time_seconds = QueueTicket::diffSecondsPositive($tsStart, $ticket->completed_at);
                $ticket->transfer_service_completed_at = $ticket->completed_at;
            }
            $ticket->completed_by = $userId;
            $ticket->save();
            if ($ticket->is_transfer && $ticket->transfer_service_time_seconds !== null) {
                \DB::table('queue_logs')->insert([
                    'queue_id' => $ticket->id,
                    'action' => 'transfer_service_recorded',
                    'old_status' => $old,
                    'new_status' => 'completed',
                    'performed_by' => $userId,
                    'remarks' => json_encode([
                        'started_at' => $ticket->transfer_service_started_at?->toDateTimeString(),
                        'completed_at' => $ticket->transfer_service_completed_at?->toDateTimeString(),
                        'seconds' => $ticket->transfer_service_time_seconds,
                    ], JSON_UNESCAPED_SLASHES),
                    'created_at' => now(),
                ]);
            }
            \DB::table('queue_logs')->insert([
                'queue_id' => $ticket->id,
                'action' => 'completed',
                'old_status' => $old,
                'new_status' => 'completed',
                'performed_by' => $userId,
                'remarks' => null,
                'created_at' => now(),
            ]);

            return true;
        });
        if (! $ok) {
            return response()->json(['error' => 'Ticket must be serving to complete'], 400);
        }

        return response()->json(['success' => true]);
    }

    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'ticket_id' => ['required', 'exists:queues,id'],
            'transaction_id' => ['required', 'exists:transactions,id'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ]);

        $user = Auth::user();
        if (! $user || ! $user->id) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $mapper = app(TransferPriorityMapper::class);

            DB::transaction(function () use ($validated, $user, $mapper) {
                $ticket = QueueTicket::lockForUpdate()->findOrFail($validated['ticket_id']);

                $fromId = (int) $ticket->transaction_id;
                $toId = (int) $validated['transaction_id'];
                $oldStatus = (string) $ticket->status;

                $match = $mapper->match($fromId, $toId);
                $ruleId = $match['rule_id'] ?? null;
                $ruleKey = $match['rule_key'] ?? null;
                $computedScore = $match['computed_score'] ?? 0;

                $wasSkipped = ($oldStatus === 'skipped') || ($ticket->skipped_by !== null);

                if (!$ticket->original_transaction_id) {
                    $ticket->original_transaction_id = $fromId;
                }

                $ticket->transaction_id = $toId;
                $ticket->counter_id = null;
                $ticket->status = 'waiting';
                $ticket->called_at = null;
                $ticket->serving_at = null;
                $ticket->is_transfer = true;
                $ticket->is_skipped_transfer = $wasSkipped;
                $ticket->transfer_priority_rule_id = $ruleId;
                $ticket->transfer_priority_score = $computedScore;
                $ticket->transfer_classified_at = now();
                $ticket->save();

                \DB::table('queue_transfers')->insert([
                    'queue_id' => $ticket->id,
                    'from_transaction_id' => $fromId,
                    'to_transaction_id' => $toId,
                    'transferred_by' => $user->id,
                    'remarks' => $validated['remarks'] ?? null,
                    'created_at' => now(),
                ]);

                $orderedIds = QueueTicket::query()
                    ->select('queues.id')
                    ->leftJoin('priorities', 'queues.priority_id', '=', 'priorities.id')
                    ->where('queues.transaction_id', $toId)
                    ->where('queues.status', 'waiting')
                    ->whereDate('queues.created_at', today())
                    ->orderByRaw('CASE WHEN priorities.priority_level IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('priorities.priority_level', 'asc')
                    ->orderBy('queues.is_transfer', 'desc')
                    ->orderBy('queues.transfer_priority_score', 'desc')
                    ->orderBy('queues.transfer_classified_at', 'asc')
                    ->orderBy('queues.id', 'asc')
                    ->pluck('queues.id')
                    ->all();

                $queuePosition = null;
                $pos = array_search($ticket->id, $orderedIds, true);
                if ($pos !== false) {
                    $queuePosition = $pos + 1;
                }

                \DB::table('queue_logs')->insert([
                    'queue_id' => $ticket->id,
                    'action' => 'transfer_prioritized',
                    'old_status' => $oldStatus,
                    'new_status' => 'waiting',
                    'performed_by' => $user->id,
                    'remarks' => json_encode([
                        'from_transaction_id' => $fromId,
                        'to_transaction_id' => $toId,
                        'matched_rule_key' => $ruleKey,
                        'computed_priority_score' => $computedScore,
                        'queue_position' => $queuePosition,
                    ], JSON_UNESCAPED_SLASHES),
                    'created_at' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            if (app()->environment('testing')) {
                throw $e;
            }

            return response()->json(['error' => 'Transfer failed'], 500);
        }

        // Clear counter cache for transferring user and all counters in affected transactions
        if ($user->counter_id) {
            if ($user->transaction_id) {
                Cache::forget(self::counterDataCacheKey($user->counter_id, $user->transaction_id));
            }
            if (isset($fromId) && $fromId) {
                Cache::forget(self::counterDataCacheKey($user->counter_id, $fromId));
            }
        }
        try {
            $txIds = array_filter([$fromId ?? null, $toId ?? null]);
            if (!empty($txIds)) {
                $cids = DB::table('users')
                    ->whereIn('transaction_id', $txIds)
                    ->whereNotNull('counter_id')
                    ->pluck('counter_id');

                foreach ($cids as $cid) {
                    foreach ($txIds as $txId) {
                        Cache::forget(self::counterDataCacheKey($cid, $txId));
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        return response()->json(['success' => true]);
    }

    public function skip(Request $request)
    {
        $request->validate(['ticket_id' => 'required|exists:queues,id']);
        $userId = Auth::id();
        DB::transaction(function () use ($request, $userId) {
            $ticket = QueueTicket::lockForUpdate()->find($request->ticket_id);
            $old = $ticket->status;
            $ticket->status = 'skipped';
            $ticket->skipped_by = $userId;
            $ticket->save();
            \DB::table('queue_logs')->insert([
                'queue_id' => $ticket->id,
                'action' => 'skipped',
                'old_status' => $old,
                'new_status' => 'skipped',
                'performed_by' => $userId,
                'remarks' => null,
                'created_at' => now(),
            ]);
        });

        return response()->json(['success' => true]);
    }

    public function cancel(Request $request)
    {
        $request->validate(['ticket_id' => 'required|exists:queues,id']);
        $user = Auth::user();
        if (! $user?->id) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $ticket = null;
        DB::transaction(function () use ($request, $user, &$ticket) {
            $ticket = QueueTicket::lockForUpdate()->find($request->ticket_id);
            $old = $ticket->status;
            $ticket->status = 'waiting';
            $ticket->counter_id = null;
            $ticket->called_at = null;
            $ticket->serving_at = null;
            $ticket->completed_at = null;
            $ticket->service_time_seconds = null;
            $ticket->waiting_time_seconds = null;
            $ticket->called_by = null;
            $ticket->serving_by = null;
            $ticket->completed_by = null;
            $ticket->skipped_by = null;
            $ticket->cancelled_by = null;
            $ticket->save();
            \DB::table('queue_logs')->insert([
                'queue_id' => $ticket->id,
                'action' => 'cancelled',
                'old_status' => $old,
                'new_status' => 'waiting',
                'performed_by' => $user->id,
                'remarks' => null,
                'created_at' => now(),
            ]);
        });

        return response()->json(['success' => true, 'ticket' => $ticket]);
    }

    public function recallSkipped(Request $request)
    {
        $request->validate(['ticket_id' => 'required|exists:queues,id']);
        $user = Auth::user();
        if (! $user->transaction_id || ! $user->counter_id) {
            return response()->json(['error' => 'User not assigned to transaction or counter'], 403);
        }
        $result = DB::transaction(function () use ($request, $user) {
            $ticket = QueueTicket::lockForUpdate()->find($request->ticket_id);
            if ($ticket->status !== 'skipped') {
                return null;
            }
            $old = $ticket->status;
            $ticket->status = 'called';
            $ticket->counter_id = $user->counter_id;
            $ticket->called_at = now();
            // Do not update waiting_time_seconds on recall; it must only be set on the first waiting->called transition
            $ticket->called_by = $user->id;
            $ticket->save();
            \DB::table('queue_logs')->insert([
                'queue_id' => $ticket->id,
                'action' => 'recalled',
                'old_status' => $old,
                'new_status' => 'called',
                'performed_by' => $user->id,
                'remarks' => null,
                'created_at' => now(),
            ]);

            return $ticket;
        });
        if (! $result) {
            return response()->json(['error' => 'Only skipped tickets can be recalled'], 400);
        }

        return response()->json(['success' => true, 'ticket' => $result]);
    }

    public function reannounce(Request $request)
    {
        $user = Auth::user();
        if (! $user?->transaction_id || ! $user?->counter_id) {
            return response()->json(['error' => 'User not assigned to transaction or counter'], 403);
        }
        $ticket = QueueTicket::query()
            ->where('counter_id', $user->counter_id)
            ->whereIn('status', ['called', 'serving'])
            ->whereDate('created_at', today())
            ->orderByDesc('called_at')
            ->first();
        if (! $ticket) {
            return response()->json(['error' => 'No active ticket to re-announce'], 400);
        }
        $reannounceLogId = \DB::table('queue_logs')->insertGetId([
            'queue_id' => $ticket->id,
            'action' => 'reannounce',
            'old_status' => $ticket->status,
            'new_status' => $ticket->status,
            'performed_by' => $user->id,
            'remarks' => null,
            'created_at' => now(),
        ]);

        // Trigger SSE update for re-announcement
        app(\App\Services\QueueEventService::class)->handleReannounce($ticket, [
            'reannounce_log_id' => (int) $reannounceLogId,
        ]);

        return response()->json(['success' => true]);
    }

    public function restoreCancelled(Request $request)
    {
        $request->validate(['ticket_id' => 'required|exists:queues,id']);
        $user = Auth::user();
        if (! $user->transaction_id || ! $user->counter_id) {
            return response()->json(['error' => 'User not assigned to transaction or counter'], 403);
        }
        $result = DB::transaction(function () use ($request, $user) {
            $ticket = QueueTicket::lockForUpdate()->find($request->ticket_id);
            if ($ticket->status !== 'cancelled') {
                return null;
            }
            $old = $ticket->status;
            $ticket->status = 'waiting';
            $ticket->counter_id = null;
            $ticket->called_at = null;
            $ticket->serving_at = null;
            $ticket->save();
            \DB::table('queue_logs')->insert([
                'queue_id' => $ticket->id,
                'action' => 'restored',
                'old_status' => $old,
                'new_status' => 'waiting',
                'performed_by' => $user->id,
                'remarks' => null,
                'created_at' => now(),
            ]);

            return $ticket;
        });
        if (! $result) {
            return response()->json(['error' => 'Only cancelled tickets can be restored'], 400);
        }

        return response()->json(['success' => true, 'ticket' => $result]);
    }

    public function list()
    {
        $transactions = Transaction::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $priorities = \App\Models\Priority::query()
            ->where('is_active', true)
            ->orderBy('priority_level')
            ->get(['id', 'name']);

        $counters = DB::table('users')
            ->whereNotNull('counter_id')
            ->distinct()
            ->orderBy('counter_id')
            ->pluck('counter_id');

        return view('queue.list', [
            'transactions' => $transactions,
            'priorities' => $priorities,
            'counters' => $counters,
        ]);
    }

    public function listData(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        $allowedPerPage = [10, 25, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }
        $page = max(1, (int) $request->query('page', 1));

        $query = QueueTicket::query()
            ->with(['transaction:id,name', 'priority:id,name', 'calledByUser:id,name'])
            ->select('queues.*');

        $date = trim((string) $request->query('date', ''));
        if ($date !== '') {
            $query->whereDate('queues.created_at', $date);
        } else {
            $query->whereDate('queues.created_at', today());
        }

        if ($request->filled('transaction_id')) {
            $query->where('queues.transaction_id', $request->query('transaction_id'));
        }
        if ($request->filled('priority_id')) {
            $query->where('queues.priority_id', $request->query('priority_id'));
        }
        if ($request->filled('counter_id')) {
            $query->where('queues.counter_id', $request->query('counter_id'));
        }
        if ($request->filled('status')) {
            $query->where('queues.status', $request->query('status'));
        }

        $search = trim((string) $request->query('q', $request->query('search', '')));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('queues.queue_number', 'like', "%{$search}%")
                    ->orWhere('queues.name', 'like', "%{$search}%");
            });
        }

        $sort = (string) $request->query('sort', 'created_at');
        $order = strtolower((string) $request->query('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSort = ['created_at', 'queue_number', 'name', 'status', 'counter_id', 'priority'];
        if (! in_array($sort, $allowedSort, true)) {
            $sort = 'created_at';
        }

        if ($sort === 'priority') {
            $query
                ->leftJoin('priorities as p', 'queues.priority_id', '=', 'p.id')
                ->orderByRaw('CASE WHEN p.priority_level IS NULL THEN 1 ELSE 0 END')
                ->orderBy('p.priority_level', $order)
                ->orderBy('queues.created_at', 'desc')
                ->select('queues.*');
        } else {
            $query->orderBy('queues.'.$sort, $order);
        }

        $paginator = $query->paginate($perPage, ['queues.*'], 'page', $page);
        $data = $paginator->getCollection()->map(function (QueueTicket $t) {
            return [
                'id' => $t->id,
                'queue_number' => $t->queue_number,
                'name' => $t->name,
                'transaction_id' => $t->transaction_id,
                'transaction_name' => $t->transaction?->name,
                'priority_id' => $t->priority_id,
                'priority_name' => $t->priority?->name,
                'counter_id' => $t->counter_id,
                'status' => $t->status,
                'called_by_name' => $t->calledByUser?->name,
                // Return only the stored first-call value; do not compute fallbacks here
                'waiting_time_seconds' => $t->waiting_time_seconds,
                'service_time_seconds' => $t->is_transfer ? $t->effectiveTransferServiceSeconds() : $t->effectiveServiceTimeSeconds(),
                'is_transfer' => (bool) $t->is_transfer,
                'transfer_service_time_seconds' => $t->is_transfer ? $t->transfer_service_time_seconds : null,
                'created_at' => $t->created_at?->format('Y-m-d H:i'),
            ];
        })->values();

        return response()->json([
            'data' => $data,
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
