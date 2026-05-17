<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function markRead(string $id)
    {
        Auth::user()->notifications()->where('id', $id)->first()?->markAsRead();

        return back();
    }

    public function markAllRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return back();
    }
}
