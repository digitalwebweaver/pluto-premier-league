<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Real chapter teams have branded logos (public/images/teams/) shown on the
 * public landing page and league views, alongside the existing crest_color
 * fallback (used where no logo is set, e.g. new teams before LT uploads one).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('crest_color');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });
    }
};
