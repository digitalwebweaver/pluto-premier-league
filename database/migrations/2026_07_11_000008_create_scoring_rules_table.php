<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Scoring rules (Phase 2E / FR-SCO-002) — the point values, LT-editable.
 * `points` may be negative. `extra_params` (json) holds shape-specific config
 * (flat/penalty for roster, multiplier for trainings) so nothing is a literal
 * in code (BR-SCO-001, BR-SCO-004).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scoring_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('subtype_label');
            $table->integer('points')->default(0); // may be negative
            $table->json('extra_params')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['category_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scoring_rules');
    }
};
