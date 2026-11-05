<?php

namespace App\Notifications;

use App\Models\LogReply;

/** Para o treinador: aluno escreveu na conversa de um treino */
class StudentMessaged extends AppNotification
{
    public function __construct(public LogReply $reply) {}

    public function preference(): string
    {
        return 'student_message';
    }

    protected function title(): string
    {
        return $this->reply->author->first_name.' respondeu sobre '.$this->reply->log->session->title;
    }

    protected function body(): ?string
    {
        return str($this->reply->body)->limit(120)->toString();
    }

    protected function icon(): string
    {
        return 'chat';
    }

    protected function url(): string
    {
        return route('trainer.logs', ['aluno' => $this->reply->log->student_id, 'filtro' => 'all']);
    }
}
