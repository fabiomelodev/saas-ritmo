<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SheetBlock extends Model
{
    protected $fillable = ['workout_sheet_id', 'section', 'sets', 'position'];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(WorkoutSheet::class, 'workout_sheet_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BlockExercise::class)->orderBy('position');
    }
}
