<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** Lista inicial de todo treinador novo (ele pode renomear, excluir e criar outras) */
    public const DEFAULTS = [
        'Membros inferiores',
        'Membros superiores',
        'Core',
        'Pliometria',
        'Mobilidade',
        'Alongamento',
    ];

    public const MOBILITY = 'Mobilidade';

    protected $fillable = ['trainer_id', 'name', 'position'];

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }

    public static function createDefaultsFor(User $trainer): void
    {
        foreach (self::DEFAULTS as $position => $name) {
            static::firstOrCreate(
                ['trainer_id' => $trainer->id, 'name' => $name],
                ['position' => $position],
            );
        }
    }

    /** Categoria usada nos blocos de mobilidade (criada se o treinador a tiver excluído) */
    public static function mobilityFor(int $trainerId): self
    {
        return static::firstOrCreate(
            ['trainer_id' => $trainerId, 'name' => self::MOBILITY],
            ['position' => (int) static::where('trainer_id', $trainerId)->max('position') + 1],
        );
    }
}
