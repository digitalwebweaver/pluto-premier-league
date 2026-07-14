<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

    public const COUNT_SUBTYPE = 'count_subtype';

    public const ROSTER_FLAT_PENALTY = 'roster_flat_penalty';

    public const BINARY_FLAT = 'binary_flat';

    public const AMOUNT_SUBTYPE = 'amount_subtype';

    public const CONDITIONAL_MULTIPLIER = 'conditional_multiplier';

    public const INPUT_SHAPES = [
        self::COUNT_SUBTYPE,
        self::ROSTER_FLAT_PENALTY,
        self::BINARY_FLAT,
        self::AMOUNT_SUBTYPE,
        self::CONDITIONAL_MULTIPLIER,
    ];

    protected $fillable = [
        'name',
        'code',
        'input_shape',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** Point values / subtypes for this category (Phase 2E). */
    public function scoringRules(): HasMany
    {
        return $this->hasMany(ScoringRule::class);
    }

    public function meetings(): BelongsToMany
    {
        return $this->belongsToMany(Meeting::class, 'meeting_categories')->withTimestamps();
    }

    /** @param  Builder<Category>  $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @param  Builder<Category>  $query */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }
}
