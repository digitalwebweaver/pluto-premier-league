<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visitors captures the guest's own name alongside the inviting member and
 * subtype (matches the source workbook's "Visitor Name" column) — the
 * invited guest is an external person, not a roster Member, so this is a
 * plain string rather than a member_id FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entry_lines', function (Blueprint $table) {
            $table->string('visitor_name')->nullable()->after('member_id');
        });
    }

    public function down(): void
    {
        Schema::table('entry_lines', function (Blueprint $table) {
            $table->dropColumn('visitor_name');
        });
    }
};
