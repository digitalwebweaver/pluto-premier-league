<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntryAttendance extends Model
{
    /** @use HasFactory<\Database\Factories\EntryAttendanceFactory> */
    use HasFactory;

    protected $table = 'entry_attendance';

    protected $fillable = [
        'meeting_entry_id',
        'member_id',
        'is_present',
        'is_on_time',
    ];

    protected function casts(): array
    {
        return [
            'is_present' => 'boolean',
            'is_on_time' => 'boolean',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(MeetingEntry::class, 'meeting_entry_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
