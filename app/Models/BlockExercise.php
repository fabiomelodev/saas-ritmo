<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockExercise extends Model
{
    protected $fillable = ['sheet_block_id', 'exercise_id', 'prescription', 'notes', 'position'];

    public function block(): BelongsTo
    {
        return $this->belongsTo(SheetBlock::class, 'sheet_block_id');
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
