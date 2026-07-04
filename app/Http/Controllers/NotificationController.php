<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = Notification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $request->user()->id)
            ->when($request->string('status')->toString() === 'unread', fn ($query) => $query->unread())
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('notifications.index', compact('notifications'));
    }

    public function read(Request $request, Notification $notification): RedirectResponse
    {
        Gate::authorize('update', $notification);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? route('notifications.index');

        return redirect()->to($url);
    }

    public function readAll(Request $request): RedirectResponse
    {
        Notification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
