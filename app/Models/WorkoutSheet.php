<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutSheet extends Model
{
    protected $fillable = ['trainer_id', 'name'];

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(SheetBlock::class)->orderBy('position');
    }

    public function strengthBlocks(): HasMany
    {
        return $this->blocks()->where('section', 'strength');
    }

    public function mobilityBlocks(): HasMany
    {
        return $this->blocks()->where('section', 'mobility');
    }
}
