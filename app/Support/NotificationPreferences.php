<?php

namespace App\Support;

use App\Models\User;

/** Tipos de aviso que cada papel pode ligar/desligar */
class NotificationPreferences
{
    public const STUDENT = [
        'daily_reminder' => ['Tiver treino no dia', 'Às 07:00'],
        'missed_log' => ['Esquecer de registrar', 'Às 20:00 do mesmo dia'],
        'trainer_reply' => ['O treinador responder', null],
        'plan_published' => ['Um plano novo for publicado', null],
    ];

    public const TRAINER = [
        'new_log' => ['Um aluno registrar um treino', 'Inclui “não fiz” e PSE alta'],
        'student_message' => ['Um aluno responder na conversa', null],
        'new_student' => ['Um novo aluno se cadastrar pelo convite', null],
    ];

    /** @return array<string, array{0: string, 1: ?string}> */
    public static function for(User $user): array
    {
        return $user->isTrainer() ? self::TRAINER : self::STUDENT;
    }
}
