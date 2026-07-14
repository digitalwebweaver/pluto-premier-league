<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Leadership Team account — `lt` guard. Login by email.
 */
class LtUser extends Authenticatable implements CanResetPasswordContract
{
    /** @use HasFactory<\Database\Factories\LtUserFactory> */
    use CanResetPassword, HasFactory, Notifiable;

    protected $fillable = [
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
}
