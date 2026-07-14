<?php

namespace App\Http\Controllers\LT;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * LT announcements (Phase 6D / FR-NOT). Posting broadcasts an in-app
 * notification to every active team. LT-only (guard:lt).
 */
class AnnouncementController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('LT/Announcements', [
            'announcements' => Announcement::with('author:id,name')->latest()->limit(50)->get()
                ->map(fn (Announcement $a) => [
                    'id' => $a->id,
                    'body' => $a->body,
                    'author' => $a->author?->name,
                    'created_at' => $a->created_at->toIso8601String(),
                ]),
        ]);
    }

    public function store(Request $request, NotificationService $notifications): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);

        Announcement::create(['lt_user_id' => $request->user('lt')->id, 'body' => $data['body']]);
        $notifications->broadcastToActiveTeams('announcement', ['body' => $data['body']]);

        return back()->with('success', 'Announcement sent to all active teams.');
    }
}
