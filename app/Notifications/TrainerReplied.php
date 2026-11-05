<?php

namespace App\Notifications;

use App\Models\LogReply;

/** Para o aluno: treinador respondeu ao registro */
class TrainerReplied extends AppNotification
{
    public function __construct(public LogReply $reply) {}

    public function preference(): string
    {
        return 'trainer_reply';
    }

    protected function title(): string
    {
        return $this->reply->author->first_name.' respondeu: '.$this->reply->log->session->title;
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
        return route('student.session', $this->reply->log->plan_session_id);
    }
}
