<?php

namespace App\Notifications;

use App\Models\PlanSession;

/**
 * Para o aluno, enviado pelo comando ritmo:lembretes:
 * - kind "today": treino do dia (07:00)
 * - kind "missed": ainda não registrou o treino de hoje (20:00)
 */
class TrainingReminder extends AppNotification
{
    public function __construct(public PlanSession $session, public string $kind = 'today') {}

    public function preference(): string
    {
        return $this->kind === 'missed' ? 'missed_log' : 'daily_reminder';
    }

    protected function title(): string
    {
        return $this->kind === 'missed'
            ? 'Você ainda não registrou: '.$this->session->title
            : 'Treino de hoje: '.$this->session->title;
    }

    protected function body(): ?string
    {
        return $this->kind === 'missed' ? 'Conte ao treinador como foi (ou marque “não fiz”).' : $this->session->summary();
    }

    protected function icon(): string
    {
        return 'calendar';
    }

    protected function url(): string
    {
        return route('student.session', $this->session);
    }

    /** Lembrete só faz sentido no mesmo dia */
    protected function pushTtl(): int
    {
        return max(60, (int) now()->diffInSeconds(now()->endOfDay()));
    }

    protected function extra(): array
    {
        return ['session_id' => $this->session->id, 'kind' => $this->kind];
    }
}
