<?php

namespace App\Http\Controllers;

use App\Models\QueueLog;
use Illuminate\Http\Request;

class QueueLogController extends Controller
{
    public function index()
    {
        return view('queue.logs');
    }

    public function data(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        $allowedPerPage = [10, 25, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }
        $page = max(1, (int) $request->query('page', 1));

        $query = QueueLog::query()
            ->with(['queue:id,queue_number,name,transaction_id', 'user:id,name'])
            ->select('queue_logs.*');

        if ($request->filled('queue_id')) {
            $query->where('queue_id', (int) $request->query('queue_id'));
        }

        $search = trim((string) $request->query('q', $request->query('search', '')));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('remarks', 'like', "%{$search}%")
                    ->orWhereHas('queue', function ($q2) use ($search) {
                        $q2->where('queue_number', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('new_status', $request->status);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('queue_logs.created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('queue_logs.created_at', '<=', $request->end_date);
        }

        $sort = (string) $request->query('sort', 'created_at');
        $order = strtolower((string) $request->query('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSort = ['created_at', 'action', 'status'];
        if (! in_array($sort, $allowedSort, true)) {
            $sort = 'created_at';
        }

        if ($sort === 'status') {
            $query->orderBy('queue_logs.new_status', $order);
        } else {
            $query->orderBy('queue_logs.'.$sort, $order);
        }

        $paginator = $query->paginate($perPage, ['queue_logs.*'], 'page', $page);
        $data = $paginator->getCollection()->map(function (QueueLog $log) {
            return [
                'id' => $log->id,
                'action' => $log->action,
                'old_status' => $log->old_status,
                'new_status' => $log->new_status,
                'remarks' => is_array($log->remarks) ? json_encode($log->remarks) : $log->remarks,
                'level' => $log->level,
                'queue_id' => $log->queue_id,
                'queue_number' => $log->queue?->queue_number,
                'queue_name' => $log->queue?->name,
                'user_name' => $log->user?->name,
                'created_at' => $log->created_at?->format('Y-m-d H:i'),
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
