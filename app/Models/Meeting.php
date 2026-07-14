<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Meeting extends Model
{
    /** @use HasFactory<\Database\Factories\MeetingFactory> */
    use HasFactory;

    public const SCHEDULED = 'scheduled';

    public const OPEN = 'open';

    public const CLOSED = 'closed';

    public const STATUSES = [self::SCHEDULED, self::OPEN, self::CLOSED];

    protected $fillable = [
        'season_id',
        'sequence_no',
        'meeting_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /** Categories that apply to this meeting (FR-MTG-005). */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'meeting_categories')->withTimestamps();
    }

    /** Teams may only create/submit entries while a meeting is open (FR-MTG-006). */
    public function isOpen(): bool
    {
        return $this->status === self::OPEN;
    }

    /** @param  Builder<Meeting>  $query */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::OPEN);
    }
}
