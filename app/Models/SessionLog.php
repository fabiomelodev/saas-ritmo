<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionLog extends Model
{
    protected $fillable = [
        'plan_session_id',
        'student_id',
        'status',
        'distance_km',
        'duration_seconds',
        'rpe',
        'comment',
        'checked_items',
        'completed_at',
        'seen_by_trainer_at',
    ];

    protected function casts(): array
    {
        return [
            'distance_km' => 'float',
            'checked_items' => 'array',
            'completed_at' => 'datetime',
            'seen_by_trainer_at' => 'datetime',
        ];
    }

    /** Registros dos alunos do treinador (de planos dele) */
    public function scopeForTrainer(Builder $query, int $trainerId): void
    {
        $query->whereHas('session.week.plan', fn ($q) => $q->where('trainer_id', $trainerId))
            ->whereIn('status', ['done', 'skipped']);
    }

    /** Ainda sem resposta do treinador */
    public function scopeAwaitingReply(Builder $query, int $trainerId): void
    {
        $query->whereDoesntHave('replies', fn ($q) => $q->where('user_id', $trainerId));
    }

    public function scopeUnseen(Builder $query): void
    {
        $query->whereNull('seen_by_trainer_at');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PlanSession::class, 'plan_session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(LogReply::class)->oldest();
    }

    /** Ritmo em min/km, ex.: 5 km em 43:00 → "8:36" */
    public function paceMinPerKm(): ?string
    {
        if (! $this->distance_km || ! $this->duration_seconds) {
            return null;
        }

        $secondsPerKm = (int) round($this->duration_seconds / $this->distance_km);

        return intdiv($secondsPerKm, 60).':'.str_pad((string) ($secondsPerKm % 60), 2, '0', STR_PAD_LEFT);
    }

    /** Velocidade em km/h, ex.: 5 km em 43:00 → 7.0 */
    public function speedKmh(): ?float
    {
        if (! $this->distance_km || ! $this->duration_seconds) {
            return null;
        }

        return round($this->distance_km / ($this->duration_seconds / 3600), 1);
    }
}
