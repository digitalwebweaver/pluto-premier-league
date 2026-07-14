<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trainings (conditional_multiplier, Phase 3D) needs a whole-team flag alongside
 * the members-present count, so per-member points can double (FR-SCO-007).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entry_lines', function (Blueprint $table) {
            $table->boolean('whole_team')->nullable()->after('count');
        });
    }

    public function down(): void
    {
        Schema::table('entry_lines', function (Blueprint $table) {
            $table->dropColumn('whole_team');
        });
    }
};
