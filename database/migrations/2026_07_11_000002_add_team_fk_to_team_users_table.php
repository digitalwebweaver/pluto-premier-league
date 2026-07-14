<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the deferred FK from `team_users.team_id` → `teams.id` (Phase 1A left it
 * a nullable, indexed column because `teams` didn't exist yet). One captain per
 * team in v1 (BR-TEAM-003); nullable so an LT-issued captain can exist briefly
 * before assignment. On team deletion the FK is nulled — but teams are never
 * hard-deleted once scored (BR-TEAM-001).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_users', function (Blueprint $table) {
            $table->foreign('team_id')->references('id')->on('teams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('team_users', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
        });
    }
};
