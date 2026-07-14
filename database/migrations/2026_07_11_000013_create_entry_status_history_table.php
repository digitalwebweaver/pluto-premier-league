<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail for every entry status transition (Phase 4A / FR-APR-008):
 * who moved it, when, from→to, and any note (e.g. a send-back reason).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entry_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_entry_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('actor_type'); // team | lt
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('meeting_entry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entry_status_history');
    }
};
