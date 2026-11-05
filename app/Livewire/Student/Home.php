<?php

namespace App\Livewire\Student;

use App\Models\LogReply;
use App\Models\Plan;
use App\Models\PlanSession;
use App\Models\PlanWeek;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Hoje')]
class Home extends Component
{
    /** Plano publicado que cobre hoje */
    #[Computed]
    public function plan(): ?Plan
    {
        return Plan::currentFor(auth()->user())?->loadCalendar();
    }

    #[Computed]
    public function week(): ?PlanWeek
    {
        $number = $this->plan?->weekNumberFor(now());

        return $number ? $this->plan->weeks->firstWhere('number', $number) : null;
    }

    #[Computed]
    public function today(): ?PlanSession
    {
        return $this->week?->sessions->firstWhere('day_of_week', now()->dayOfWeekIso);
    }

    /** Treino mais recente que passou sem registro (últimos 7 dias) */
    #[Computed]
    public function pending(): ?PlanSession
    {
        if (! $this->plan) {
            return null;
        }

        $limit = now()->subDays(7)->startOfDay();

        return $this->plan->weeks->flatMap->sessions
            ->filter(fn (PlanSession $s) => $s->status() === 'pending' && $s->date()->gte($limit))
            ->sortByDesc(fn (PlanSession $s) => $s->date()->timestamp)
            ->first();
    }

    /** Próximos dias da semana atual */
    #[Computed]
    public function upcoming()
    {
        return $this->week?->sessions
            ->filter(fn (PlanSession $s) => $s->date()->isAfter(now()->endOfDay()))
            ->take(3) ?? collect();
    }

    /** Última resposta do treinador (últimos 7 dias) */
    #[Computed]
    public function latestReply(): ?LogReply
    {
        return LogReply::where('user_id', '!=', auth()->id())
            ->where('created_at', '>=', now()->subDays(7))
            ->whereHas('log', fn ($q) => $q->where('student_id', auth()->id()))
            ->with(['author', 'log.session'])
            ->latest()
            ->first();
    }

    #[Computed]
    public function progress(): array
    {
        $sessions = $this->week?->sessions->reject->isRest() ?? collect();

        return [
            'total' => $sessions->count(),
            'done' => $sessions->filter(fn ($s) => $s->log?->status === 'done')->count(),
            'km' => (float) $sessions->sum(fn ($s) => $s->log?->status === 'done' ? (float) $s->log->distance_km : 0),
            'goal' => $this->week?->goal_km,
            'states' => $sessions->map(fn ($s) => $s->status())->values()->all(),
        ];
    }

    public function render()
    {
        return view('livewire.student.home', [
            'trainer' => auth()->user()->currentTrainer(),
        ]);
    }
}
