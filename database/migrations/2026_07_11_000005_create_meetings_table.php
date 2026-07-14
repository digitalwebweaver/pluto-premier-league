<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Meetings (Phase 2C) — the league's scoring periods. Status lifecycle
 * scheduled → open → closed (FR-MTG-003). Belongs to a season (FR-MTG-007).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence_no');
            $table->date('meeting_date');
            $table->string('status')->default('scheduled'); // scheduled | open | closed
            $table->timestamps();

            $table->unique(['season_id', 'sequence_no']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
