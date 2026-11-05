<?php

namespace App\Enums;

enum Role: string
{
    case Trainer = 'trainer';
    case Student = 'student';

    public function label(): string
    {
        return match ($this) {
            self::Trainer => 'Treinador',
            self::Student => 'Aluno',
        };
    }
}
