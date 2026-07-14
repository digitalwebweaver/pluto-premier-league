<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Team members / roster (Phase 2B). A member belongs to exactly one team
 * (FR-MBR-004) and is never hard-deleted once they have history — only
 * deactivated (BR-MBR-001).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('business_category')->nullable(); // free text v1
            $table->string('photo_path')->nullable();          // upload deferred
            $table->string('avatar_color', 7)->default('#5A6684');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['team_id', 'is_active']); // plan/02 index
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
