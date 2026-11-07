<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return view('portal.notifications', [
            'notifications' => $request->user()->notifications()->paginate(20),
        ]);
    }

    public function markAsRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'Semua pemberitahuan ditandai sudah dibaca.');
    }
}
