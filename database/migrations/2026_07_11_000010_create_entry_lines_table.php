<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entry lines (Phase 3A) — the repeatable rows for count/amount categories
 * (member, subtype, count [+ amount/from_member for TYFCB]). `computed_points`
 * is the server-computed value for the line.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scoring_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('from_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->unsignedInteger('count')->default(0);
            $table->decimal('amount', 12, 2)->nullable(); // ₹ for TYFCB
            $table->integer('computed_points')->default(0);
            $table->string('evidence_path')->nullable(); // Team/Joint Meeting photo (optional)
            $table->timestamps();

            $table->index('meeting_entry_id');
            $table->index('category_id');
            $table->index('member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entry_lines');
    }
};
