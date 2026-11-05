<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogReply extends Model
{
    protected $fillable = ['session_log_id', 'user_id', 'body'];

    public function log(): BelongsTo
    {
        return $this->belongsTo(SessionLog::class, 'session_log_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
