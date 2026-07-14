<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeetingEntry extends Model
{
    /** @use HasFactory<\Database\Factories\MeetingEntryFactory> */
    use BelongsToTeam, HasFactory;

    public const DRAFT = 'draft';

    public const SUBMITTED = 'submitted';

    public const APPROVED = 'approved';

    public const SENT_BACK = 'sent_back';

    protected $fillable = [
        'team_id',
        'meeting_id',
        'status',
        'computed_total',
        'points_snapshot',
        'submitted_at',
        'approved_by',
        'approved_at',
        'sent_back_note',
    ];

    protected function casts(): array
    {
        return [
            'computed_total' => 'integer',
            'points_snapshot' => 'array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(EntryLine::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(EntryAttendance::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(LtUser::class, 'approved_by');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(EntryStatusHistory::class);
    }

    public function isDraft(): bool
    {
        return $this->status === self::DRAFT;
    }

    public function isApproved(): bool
    {
        return $this->status === self::APPROVED;
    }

    /** A team may still edit while draft, submitted, or sent back — not once approved. */
    public function isEditableByTeam(): bool
    {
        return in_array($this->status, [self::DRAFT, self::SUBMITTED, self::SENT_BACK], true);
    }
}
