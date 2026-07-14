<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Team;

/**
 * Emits in-app notifications (Phase 6D). Scoped per team; a broadcast fans out
 * to every active team (BR-NOT — inactive teams excluded). Email delivery
 * (respecting `notification_pref`) is an optional add-on later.
 */
class NotificationService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function notifyTeam(?int $teamId, string $type, array $payload = []): void
    {
        if ($teamId === null) {
            return; // e.g. a captain not yet linked to a team
        }

        Notification::create(['team_id' => $teamId, 'type' => $type, 'payload' => $payload]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function broadcastToActiveTeams(string $type, array $payload = []): void
    {
        foreach (Team::active()->pluck('id') as $teamId) {
            $this->notifyTeam($teamId, $type, $payload);
        }
    }
}
