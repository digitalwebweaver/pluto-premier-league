<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leadership Team accounts — backs the `lt` auth guard.
 * Schema per plan/02-data-model-schema.md. Login identifier is `email`
 * (owner-confirmed 2026-07-11).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lt_users', function (Blueprint $table) {
            $table->id();
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
        Schema::dropIfExists('lt_users');
    }
};
