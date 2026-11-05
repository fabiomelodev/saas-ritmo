<?php

namespace App\Enums;

enum SessionType: string
{
    case Run = 'run';
    case Strength = 'strength';
    case Rest = 'rest';

    public function label(): string
    {
        return match ($this) {
            self::Run => 'Corrida',
            self::Strength => 'Força',
            self::Rest => 'Descanso',
        };
    }
}
