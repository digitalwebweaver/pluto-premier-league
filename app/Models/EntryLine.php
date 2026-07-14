<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntryLine extends Model
{
    /** @use HasFactory<\Database\Factories\EntryLineFactory> */
    use HasFactory;

    protected $fillable = [
        'meeting_entry_id',
        'category_id',
        'scoring_rule_id',
        'member_id',
        'visitor_name',
        'from_member_id',
        'count',
        'whole_team',
        'amount',
        'computed_points',
        'evidence_path',
    ];

    protected function casts(): array
    {
        return [
            'count' => 'integer',
            'whole_team' => 'boolean',
            'amount' => 'decimal:2',
            'computed_points' => 'integer',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(MeetingEntry::class, 'meeting_entry_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scoringRule(): BelongsTo
    {
        return $this->belongsTo(ScoringRule::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
