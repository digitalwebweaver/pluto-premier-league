<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Scoring categories (Phase 2D / FR-SCO-001). The `input_shape` drives how the
 * entry form and ScoringService (Phase 2E) treat the category. Point VALUES
 * never live here — they live in `scoring_rules` (BR-SCO-001).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 8)->unique();
            // count_subtype | roster_flat_penalty | binary_flat | amount_subtype | conditional_multiplier
            $table->string('input_shape');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
