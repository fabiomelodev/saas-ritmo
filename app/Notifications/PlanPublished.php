<?php

namespace App\Notifications;

use App\Models\Plan;

/** Para o aluno: treinador publicou um plano */
class PlanPublished extends AppNotification
{
    public function __construct(public Plan $plan) {}

    public function preference(): string
    {
        return 'plan_published';
    }

    protected function title(): string
    {
        return $this->plan->trainer->first_name.' publicou o '.str($this->plan->title)->lcfirst();
    }

    protected function body(): ?string
    {
        return 'Começa em '.$this->plan->starts_on->translatedFormat('l, j \\d\\e F').'.';
    }

    protected function icon(): string
    {
        return 'calendar';
    }

    protected function url(): string
    {
        return route('student.plan');
    }
}
