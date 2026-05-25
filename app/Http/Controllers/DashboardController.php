<?php

namespace App\Http\Controllers;

use App\Models\QueueTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    public function stats(Request $request)
    {
        $period = $request->input('period', 'today');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Determine date range
        $now = Carbon::now();

        if ($period === 'today') {
            $start = $now->copy()->startOfDay();
            $end = $now->copy()->endOfDay();
        } elseif ($period === 'week') {
            $start = $now->copy()->startOfWeek();
            $end = $now->copy()->endOfWeek();
        } elseif ($period === 'month') {
            $start = $now->copy()->startOfMonth();
            $end = $now->copy()->endOfMonth();
        } elseif ($period === 'custom' && $startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
        } else {
            $start = $now->copy()->startOfDay();
            $end = $now->copy()->endOfDay();
        }

        // Filter Query
        $baseQuery = QueueTicket::query()
            ->whereBetween('queues.created_at', [$start, $end]);

        // KPIs
        $totalTickets = (clone $baseQuery)->count();
        $servedTickets = (clone $baseQuery)->whereIn('status', ['completed', 'serving'])->count();
        $waitingTickets = (clone $baseQuery)->where('status', 'waiting')->count();
        $cancelledTickets = (clone $baseQuery)->where('status', 'cancelled')->count();
        $skippedTickets = (clone $baseQuery)->where('status', 'skipped')->count();

        // Averages (in seconds)
        $avgWaitTime = (clone $baseQuery)->whereNotNull('waiting_time_seconds')->avg('waiting_time_seconds');
        $avgServiceTime = (clone $baseQuery)->whereNotNull('service_time_seconds')->avg('service_time_seconds');

        // Charts Data

        // 1. Status Distribution
        $statusCounts = (clone $baseQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Ensure all statuses are present for consistent coloring
        $statuses = ['waiting', 'serving', 'completed', 'skipped', 'cancelled'];
        $statusData = [];
        foreach ($statuses as $status) {
            $statusData[$status] = $statusCounts[$status] ?? 0;
        }

        // 2. Transaction Volume
        $transactionVolume = (clone $baseQuery)
            ->join('transactions', 'queues.transaction_id', '=', 'transactions.id')
            ->select('transactions.name', DB::raw('count(*) as count'))
            ->groupBy('transactions.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // 3. Timeline (Hourly for Today, Daily for Week/Month)
        if ($period === 'today') {
            $timelineData = (clone $baseQuery)
                ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
                ->groupBy('hour')
                ->orderBy('hour')
                ->pluck('count', 'hour')
                ->toArray();

            // Fill missing hours
            $timeline = ['labels' => [], 'data' => []];
            for ($i = 6; $i <= 18; $i++) { // 6 AM to 6 PM usually
                $label = Carbon::createFromTime($i, 0)->format('g A');
                $timeline['labels'][] = $label;
                $timeline['data'][] = $timelineData[$i] ?? 0;
            }
        } else {
            $timelineData = (clone $baseQuery)
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date')
                ->toArray();

            $timeline = ['labels' => [], 'data' => []];
            $current = $start->copy();
            while ($current <= $end) {
                $dateStr = $current->format('Y-m-d');
                $timeline['labels'][] = $current->format('M d');
                $timeline['data'][] = $timelineData[$dateStr] ?? 0;
                $current->addDay();
            }
        }

        return response()->json([
            'kpi' => [
                'total' => $totalTickets,
                'served' => $servedTickets,
                'waiting' => $waitingTickets,
                'cancelled' => $cancelledTickets,
                'skipped' => $skippedTickets,
                'avg_wait' => $avgWaitTime ? round($avgWaitTime / 60, 1) : 0,
                'avg_service' => $avgServiceTime ? round($avgServiceTime / 60, 1) : 0,
            ],
            'charts' => [
                'status' => $statusData,
                'transaction' => [
                    'labels' => $transactionVolume->pluck('name'),
                    'data' => $transactionVolume->pluck('count'),
                ],
                'timeline' => $timeline,
            ],
        ]);
    }
}
