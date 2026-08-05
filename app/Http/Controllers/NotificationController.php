<?php

namespace App\Http\Controllers;

use App\Models\Notification;
class NotificationController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $notifications = $user->notifications()->paginate(15);
        return View('Notifications.index',compact('notifications'));
    }

    public function read(Notification $notification)
    {
        $notification->update(['read_at' => now()]);
        return redirect()->back();
    }
}
