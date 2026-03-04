<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // Fetch unread + recent (used by bell polling)
public function index(Request $request)
{
    // Temporarily bypass tenant for debugging
    $user = $request->user();
    if (!$user) return response()->json(['notifications' => [], 'unread_count' => 0]);
    
    $tenantId = $user->tenant_id;
    if (!$tenantId) return response()->json(['notifications' => [], 'unread_count' => 0]);

    $notifications = \App\Models\AppNotification::where('user_id', $user->id)
        ->where('tenant_id', $tenantId)
        ->orderBy('created_at', 'desc')
        ->take(20)
        ->get();

    return response()->json([
        'notifications' => $notifications,
        'unread_count'  => $notifications->whereNull('read_at')->count(),
    ]);
}

    // Mark one as read
    public function markRead(Request $request, int $id)
    {
        $notification = AppNotification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $notification->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    // Mark all as read
    public function markAllRead(Request $request)
    {
        AppNotification::where('user_id', $request->user()->id)
            ->where('tenant_id', app('tenant')->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}