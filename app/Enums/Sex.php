<?php

namespace App\Enums;

enum Sex: string
{
    case Female = 'female';
    case Male = 'male';

    public function label(): string
    {
        return match ($this) {
            self::Female => 'Feminino',
            self::Male => 'Masculino',
        };
    }
}
