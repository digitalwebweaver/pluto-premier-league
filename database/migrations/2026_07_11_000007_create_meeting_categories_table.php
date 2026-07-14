<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which categories apply to a given meeting (Phase 2D / FR-MTG-005).
 * Default for a new meeting = the full active set (BR-MTG-002).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['meeting_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_categories');
    }
};
