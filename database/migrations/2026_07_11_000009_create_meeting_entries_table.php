<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Meeting entries (Phase 3A) — one per (team, meeting). Header for a team's
 * scorecard: status workflow draft → submitted → approved | sent_back
 * (FR-ENT-013, unique team+meeting). `points_snapshot` is written on approval
 * so later scoring_rule edits never rewrite history (BR-SCO-003).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('draft'); // draft|submitted|approved|sent_back
            $table->integer('computed_total')->default(0);
            $table->json('points_snapshot')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('lt_users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('sent_back_note')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'meeting_id']);
            $table->index('status');
            $table->index(['meeting_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_entries');
    }
};
