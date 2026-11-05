<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalRecord extends Model
{
    protected $fillable = ['student_id', 'label', 'distance_km', 'time_seconds', 'achieved_on'];

    protected function casts(): array
    {
        return [
            'distance_km' => 'float',
            'achieved_on' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /** 2070 s → "34:30"; 4530 s → "1:15:30" */
    public function timeLabel(): string
    {
        $h = intdiv($this->time_seconds, 3600);
        $m = intdiv($this->time_seconds % 3600, 60);
        $s = $this->time_seconds % 60;

        return $h > 0
            ? sprintf('%d:%02d:%02d', $h, $m, $s)
            : sprintf('%d:%02d', $m, $s);
    }

    /** Mesmo cálculo da planilha: (tempo / distância) em min/km */
    public function paceLabel(): string
    {
        $perKm = (int) floor($this->time_seconds / $this->distance_km);

        return sprintf('%d:%02d min/km', intdiv($perKm, 60), $perKm % 60);
    }
}
