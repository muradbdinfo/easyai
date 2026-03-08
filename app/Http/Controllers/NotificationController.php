<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $query = AppNotification::where('user_id', $user->id);

        // Superadmin has no tenant_id — query only by user_id
        // Tenant users are also scoped by tenant_id for extra safety
        if ($user->tenant_id) {
            $query->where('tenant_id', $user->tenant_id);
        }

        $notifications = $query->orderBy('created_at', 'desc')->take(20)->get();

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $notifications->whereNull('read_at')->count(),
        ]);
    }

    public function markRead(Request $request, int $id)
    {
        if (!$request->user()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        AppNotification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function markAllRead(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        AppNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}