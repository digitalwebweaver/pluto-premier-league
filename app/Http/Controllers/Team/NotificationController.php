<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Captain's in-app notifications (Phase 6D). Own-team scoped; viewing marks
 * them read so the shell badge clears.
 */
class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $teamId = $request->user('team')->team_id;

        $notifications = $teamId
            ? Notification::forTeam($teamId)->latest()->limit(50)->get()
            : collect();

        $data = $notifications->map(fn (Notification $n) => [
            'id' => $n->id,
            'type' => $n->type,
            'payload' => $n->payload,
            'is_new' => $n->read_at === null,
            'created_at' => $n->created_at->toIso8601String(),
        ]);

        // Mark everything read now that the captain has seen the list.
        if ($teamId) {
            Notification::forTeam($teamId)->unread()->update(['read_at' => now()]);
        }

        return Inertia::render('Team/Notifications', ['notifications' => $data]);
    }
}
