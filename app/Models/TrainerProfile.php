<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TrainerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'cref',
        'city',
        'state',
        'bio',
        'invite_code',
        'accepting_invites',
    ];

    protected function casts(): array
    {
        return [
            'accepting_invites' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateInviteCode(): string
    {
        do {
            $code = Str::lower(Str::random(8));
        } while (static::where('invite_code', $code)->exists());

        return $code;
    }

    /** Troca o link: o antigo deixa de funcionar, vínculos existentes continuam */
    public function regenerateInviteCode(): void
    {
        $this->update(['invite_code' => static::generateInviteCode()]);
    }

    public function inviteUrl(): string
    {
        return route('invite.show', $this->invite_code);
    }
}
