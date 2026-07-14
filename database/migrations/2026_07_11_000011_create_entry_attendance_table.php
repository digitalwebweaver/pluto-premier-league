<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entry attendance (Phase 3A) — per-member present/on-time marks that back the
 * Attendance & Punctuality roster_flat_penalty math (FR-ENT-005).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entry_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_present')->default(true);
            $table->boolean('is_on_time')->default(true);
            $table->timestamps();

            $table->unique(['meeting_entry_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entry_attendance');
    }
};
