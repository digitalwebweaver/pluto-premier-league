<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Team captain account — `team` guard. Login by email. Carries `team_id`
 * (FK constraint added in Phase 2A). A team relationship is added once the
 * Team model exists (Phase 2A).
 */
class TeamUser extends Authenticatable implements CanResetPasswordContract
{
    /** @use HasFactory<\Database\Factories\TeamUserFactory> */
    use CanResetPassword, HasFactory, Notifiable;

    protected $fillable = [
        'team_id',
        'name',
        'email',
        'password',
        'must_set_password',
        'notification_pref',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'must_set_password' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
