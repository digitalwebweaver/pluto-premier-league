<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Team captain accounts — backs the `team` auth guard.
 * Schema per plan/02-data-model-schema.md. Login identifier is `email`
 * (owner-confirmed 2026-07-11). Each captain carries a `team_id`.
 *
 * NOTE: the `team_id` FOREIGN KEY constraint is added in Phase 2A, once the
 * `teams` table exists. For now it is a nullable, indexed column so 1A stays
 * self-contained and `migrate` runs before any league-core tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('must_set_password')->default(false);
            $table->string('notification_pref')->default('email');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_users');
    }
};
