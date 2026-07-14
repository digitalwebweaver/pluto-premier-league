<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntryStatusHistory extends Model
{
    protected $table = 'entry_status_history';

    public $timestamps = false;

    protected $fillable = [
        'meeting_entry_id',
        'from_status',
        'to_status',
        'actor_type',
        'actor_id',
        'note',
        'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(MeetingEntry::class, 'meeting_entry_id');
    }
}
