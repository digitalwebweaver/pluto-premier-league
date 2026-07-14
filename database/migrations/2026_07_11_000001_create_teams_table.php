<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * League teams (Phase 2A). Schema per plan/02-data-model-schema.md.
 * Teams are never hard-deleted once they've scored — only deactivated
 * (BR-TEAM-001); history is preserved via FKs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('short_code', 4);            // auto-derived initials (FR-TEAM-007)
            $table->string('crest_color', 7);           // hex, e.g. #1B2F52
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
