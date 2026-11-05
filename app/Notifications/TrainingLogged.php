<?php

namespace App\Notifications;

use App\Models\SessionLog;

/** Para o treinador: aluno registrou (ou marcou "não fiz") */
class TrainingLogged extends AppNotification
{
    public function __construct(public SessionLog $log) {}

    public function preference(): string
    {
        return 'new_log';
    }

    protected function title(): string
    {
        $name = $this->log->student->first_name;
        $title = $this->log->session->title;

        return $this->log->status === 'skipped'
            ? "{$name} não fez: {$title}"
            : "{$name} registrou: {$title}";
    }

    protected function body(): ?string
    {
        if ($this->log->status === 'skipped') {
            return $this->log->comment;
        }

        return collect([
            $this->log->distance_km ? str_replace('.', ',', (string) $this->log->distance_km).' km' : null,
            $this->log->paceMinPerKm() ? $this->log->paceMinPerKm().' min/km' : null,
            $this->log->rpe ? 'PSE '.$this->log->rpe : null,
        ])->filter()->join(' · ') ?: $this->log->comment;
    }

    protected function icon(): string
    {
        return $this->log->status === 'skipped' ? 'calendar' : 'check';
    }

    protected function url(): string
    {
        return route('trainer.logs', ['aluno' => $this->log->student_id, 'filtro' => 'all']);
    }
}
