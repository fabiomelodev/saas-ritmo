<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TrainerStudent extends Pivot
{
    protected $table = 'trainer_student';

    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }
}
