<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Team extends Model
{
    /** @use HasFactory<\Database\Factories\TeamFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'short_code',
        'crest_color',
        'logo_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** One captain login per team in v1 (BR-TEAM-003). */
    public function captain(): HasOne
    {
        return $this->hasOne(TeamUser::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /** @param  Builder<Team>  $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Derive up-to-4-char initials from a name (FR-TEAM-007):
     * "Digital Titans" → "DT", "Apex" → "AP".
     */
    public static function deriveShortCode(string $name): string
    {
        $words = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($words) >= 2) {
            $initials = '';
            foreach ($words as $word) {
                $initials .= mb_substr($word, 0, 1);
            }

            return mb_strtoupper(mb_substr($initials, 0, 4));
        }

        return mb_strtoupper(mb_substr($words[0] ?? '?', 0, 2));
    }
}
