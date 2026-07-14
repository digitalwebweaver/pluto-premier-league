<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoringRule extends Model
{
    /** @use HasFactory<\Database\Factories\ScoringRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id',
        'subtype_label',
        'points',
        'extra_params',
        'is_active',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'extra_params' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @param  Builder<ScoringRule>  $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** A single extra param with a fallback (flat/penalty/multiplier/metric). */
    public function param(string $key, mixed $default = null): mixed
    {
        return $this->extra_params[$key] ?? $default;
    }
}
