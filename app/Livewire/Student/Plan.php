<?php

namespace App\Livewire\Student;

use App\Models\Plan as PlanModel;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Plano do mês: abas de semana + os 7 dias com status */
#[Layout('layouts.app')]
#[Title('Plano do mês')]
class Plan extends Component
{
    #[Url(as: 'semana')]
    public ?int $week = null;

    #[Computed]
    public function plan(): ?PlanModel
    {
        // Plano atual; se não houver, o último publicado (para rever o mês anterior)
        $plan = PlanModel::currentFor(auth()->user())
            ?? auth()->user()->plans()->published()->withActiveLink()->latest('starts_on')->first();

        return $plan?->loadCalendar();
    }

    public function mount(): void
    {
        if ($this->week === null && $this->plan) {
            $this->week = $this->plan->weekNumberFor(now()) ?? 1;
        }
    }

    #[Computed]
    public function selectedWeek()
    {
        return $this->plan?->weeks->firstWhere('number', $this->week) ?? $this->plan?->weeks->first();
    }

    public function render()
    {
        return view('livewire.student.plan');
    }
}
