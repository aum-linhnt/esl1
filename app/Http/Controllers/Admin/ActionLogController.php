<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserActionLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class ActionLogController extends Controller
{
    /**
     * Display action logs with filters.
     */
    public function index(Request $request)
    {
        $query = UserActionLog::with('user')->latest('created_at');

        // Filter by action type
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by search (user name or description)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
        }

        $logs = $query->paginate(30)->withQueryString();

        // Summary stats
        $todayCount = UserActionLog::today()->count();
        $weekCount = UserActionLog::recent(7)->count();
        $totalCount = UserActionLog::count();

        // Action distribution today
        $todayActions = UserActionLog::today()
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderByDesc('count')
            ->pluck('count', 'action')
            ->toArray();

        // Top active users today
        $topUsersToday = UserActionLog::today()
            ->selectRaw('user_id, COUNT(*) as action_count')
            ->groupBy('user_id')
            ->orderByDesc('action_count')
            ->take(5)
            ->with('user')
            ->get();

        // Available action types for filter
        $actionLabels = UserActionLog::actionLabels();

        return view('admin.logs.index', compact(
            'logs',
            'todayCount',
            'weekCount',
            'totalCount',
            'todayActions',
            'topUsersToday',
            'actionLabels'
        ));
    }

    /**
     * Export action logs as CSV.
     */
    public function exportCsv(Request $request)
    {
        $filename = 'action_logs_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($request) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
            fputcsv($handle, ['ID', 'Người dùng', 'Email', 'Hành động', 'Mô tả', 'IP', 'Thời gian']);

            $query = UserActionLog::with('user')->latest('created_at');

            if ($request->filled('action')) {
                $query->where('action', $request->action);
            }
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }
            if ($request->filled('date_from')) {
                $query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
            }
            if ($request->filled('date_to')) {
                $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
            }

            $query->chunk(200, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->id,
                        $log->user->name ?? '',
                        $log->user->email ?? '',
                        $log->action,
                        $log->description ?? '',
                        $log->ip_address ?? '',
                        $log->created_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }
}
