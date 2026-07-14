<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $fillable = ['lt_user_id', 'body'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(LtUser::class, 'lt_user_id');
    }
}
