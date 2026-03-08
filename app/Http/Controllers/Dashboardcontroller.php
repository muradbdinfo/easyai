<?php

// FILE: app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Models\Plan;
use App\Models\Project;
use App\Models\UsageLog;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $tenant = app('tenant');
        $tid    = $tenant->id;

        $totalProjects = Project::where('tenant_id', $tid)->count();
        $totalChats    = Chat::where('tenant_id', $tid)->count();
        $openChats     = Chat::where('tenant_id', $tid)->where('status', 'open')->count();
        $tokensToday   = (int) UsageLog::where('tenant_id', $tid)->whereDate('created_at', today())->sum('total_tokens');
        $tokensMonth   = (int) UsageLog::where('tenant_id', $tid)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total_tokens');
        $messagesMonth = (int) Message::where('tenant_id', $tid)->where('role', 'user')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();

        // Last 7 days daily usage
        $dailyUsage = UsageLog::where('tenant_id', $tid)
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_tokens) as tokens'),
                DB::raw('COUNT(*) as messages')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Fill missing days
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $d      = now()->subDays($i)->toDateString();
            $days[] = [
                'date'     => $d,
                'label'    => now()->subDays($i)->format('D'),
                'tokens'   => (int) ($dailyUsage[$d]->tokens   ?? 0),
                'messages' => (int) ($dailyUsage[$d]->messages ?? 0),
            ];
        }

        $recentChats = Chat::where('tenant_id', $tid)
            ->with('project:id,name')
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get(['id', 'project_id', 'title', 'status', 'total_tokens', 'updated_at']);

        // All active plans for the interactive plan card
        $plans = Plan::where('is_active', true)
            ->orderBy('price')
            ->get(['id', 'name', 'price', 'monthly_token_limit', 'features']);

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_projects' => $totalProjects,
                'total_chats'    => $totalChats,
                'open_chats'     => $openChats,
                'tokens_today'   => $tokensToday,
                'tokens_month'   => $tokensMonth,
                'messages_month' => $messagesMonth,
            ],
            'daily_usage'  => $days,
            'recent_chats' => $recentChats,
            'plans'        => $plans,
        ]);
    }
}