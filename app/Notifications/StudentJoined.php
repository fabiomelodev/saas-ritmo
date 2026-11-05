<?php

namespace App\Notifications;

use App\Models\User;

/** Para o treinador: novo aluno pelo link de convite */
class StudentJoined extends AppNotification
{
    public function __construct(public User $student) {}

    public function preference(): string
    {
        return 'new_student';
    }

    protected function title(): string
    {
        return $this->student->name.' entrou pelo seu convite';
    }

    protected function body(): ?string
    {
        return 'Monte o primeiro plano para começar.';
    }

    protected function icon(): string
    {
        return 'users';
    }

    protected function url(): string
    {
        return route('trainer.students.show', $this->student);
    }
}
