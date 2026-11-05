<?php

namespace App\Livewire\Trainer;

use App\Models\Plan;
use App\Models\SessionLog;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Perfil do aluno visto pelo treinador */
#[Layout('layouts.app')]
#[Title('Aluno')]
class StudentProfile extends Component
{
    public const LEVELS = ['Iniciante', 'Intermediário', 'Avançado'];

    #[Locked]
    public int $studentId;

    public string $goal = '';

    public string $level = '';

    public string $notes = '';

    public function mount(User $student): void
    {
        $link = $this->linkFor($student->id);
        abort_unless($link, 404);

        $this->studentId = $student->id;
        $this->goal = (string) $link->pivot->goal;
        $this->level = (string) $link->pivot->level;
        $this->notes = (string) $link->pivot->private_notes;
    }

    private function linkFor(int $studentId): ?User
    {
        return auth()->user()->students()->whereKey($studentId)->first();
    }

    #[Computed]
    public function student(): User
    {
        return $this->linkFor($this->studentId) ?? abort(404);
    }

    #[Computed]
    public function plan(): ?Plan
    {
        $plan = Plan::where('trainer_id', auth()->id())
            ->where('student_id', $this->studentId)
            ->latest('starts_on')
            ->first();

        return $plan?->loadCalendar();
    }

    #[Computed]
    public function summary(): array
    {
        $sessions = $this->plan?->weeks->flatMap->sessions->reject->isRest() ?? collect();
        $done = $sessions->filter(fn ($s) => $s->log?->status === 'done');
        $rpes = $done->pluck('log.rpe')->filter();

        return [
            'trainings' => $sessions->count(),
            'done' => $done->count(),
            'km' => (float) $done->sum(fn ($s) => (float) $s->log->distance_km),
            'goal' => (float) ($this->plan?->weeks->sum('goal_km') ?? 0),
            'rpe' => $rpes->isNotEmpty() ? round($rpes->avg(), 1) : null,
            'weeks' => $this->plan?->weeks->map(fn ($w) => [
                'number' => $w->number,
                'goal' => (float) $w->goal_km,
                'done' => (float) $w->sessions->sum(fn ($s) => $s->log?->status === 'done' ? (float) $s->log->distance_km : 0),
            ])->all() ?? [],
        ];
    }

    #[Computed]
    public function logs()
    {
        return SessionLog::forTrainer(auth()->id())
            ->where('student_id', $this->studentId)
            ->with(['student', 'session.week.plan', 'session.workoutSheet', 'replies.author'])
            ->latest('completed_at')
            ->limit(3)
            ->get();
    }

    protected function rules(): array
    {
        return [
            'goal' => ['nullable', 'string', 'max:120'],
            'level' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return ['goal' => 'objetivo', 'level' => 'nível', 'notes' => 'anotações'];
    }

    public function save(): void
    {
        $this->validate();

        auth()->user()->students()->updateExistingPivot($this->studentId, [
            'goal' => $this->goal ?: null,
            'level' => $this->level ?: null,
            'private_notes' => $this->notes ?: null,
        ]);

        unset($this->student);
        session()->now('saved', 'Dados do aluno salvos.');
    }

    /** Arquivado: perde o acesso ao plano; histórico fica guardado */
    public function archive(): void
    {
        auth()->user()->students()->updateExistingPivot($this->studentId, ['status' => 'archived', 'archived_at' => now()]);
        unset($this->student);
        session()->now('saved', 'Aluno arquivado. Ele não vê mais o plano.');
    }

    public function restore(): void
    {
        auth()->user()->students()->updateExistingPivot($this->studentId, ['status' => 'active', 'archived_at' => null]);
        unset($this->student);
        session()->now('saved', 'Aluno reativado.');
    }

    public function render()
    {
        return view('livewire.trainer.student-profile', ['levels' => self::LEVELS])
            ->title($this->student->name);
    }
}
