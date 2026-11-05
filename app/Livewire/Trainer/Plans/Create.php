<?php

namespace App\Livewire\Trainer\Plans;

use App\Actions\CreatePlan;
use App\Models\Plan;
use App\Models\User;
use App\Support\WeekRange;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Novo plano')]
class Create extends Component
{
    #[Url(as: 'aluno')]
    public ?int $studentId = null;

    public string $title = '';

    public string $startsOn = '';

    /** duplicate | blank */
    public string $base = 'blank';

    /** @var array<int, mixed> */
    public array $goals = [1 => null, 2 => null, 3 => null, 4 => null];

    public bool $increase = false;

    public int $increasePercent = 10;

    private bool $titleWasDefault = true;

    public function mount(): void
    {
        $this->studentId ??= $this->students->first()?->id;
        $this->applyDefaults();
    }

    #[Computed]
    public function students()
    {
        return auth()->user()->activeStudents()->orderBy('name')->get();
    }

    #[Computed]
    public function previousPlan(): ?Plan
    {
        if (! $this->studentId) {
            return null;
        }

        return Plan::where('trainer_id', auth()->id())
            ->where('student_id', $this->studentId)
            ->with('weeks.sessions')
            ->latest('starts_on')
            ->first();
    }

    public function updatedStudentId(): void
    {
        unset($this->previousPlan);
        $this->applyDefaults();
    }

    /** Só troca o título se o treinador não tiver escrito um próprio */
    public function updatingStartsOn(): void
    {
        $this->titleWasDefault = $this->title === '' || $this->title === $this->defaultTitle();
    }

    public function updatedStartsOn(): void
    {
        unset($this->firstMonday, $this->weekRanges);

        if ($this->titleWasDefault) {
            $this->title = $this->defaultTitle();
        }
    }

    /** Segunda-feira em que o plano vai começar (a data escolhida é ajustada para a segunda da mesma semana) */
    #[Computed]
    public function firstMonday(): ?CarbonImmutable
    {
        try {
            return CarbonImmutable::parse($this->startsOn)->startOfWeek(CarbonImmutable::MONDAY);
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return array<int, string> número da semana => "09–15/nov" */
    #[Computed]
    public function weekRanges(): array
    {
        $monday = $this->firstMonday;
        if (! $monday) {
            return [];
        }

        return collect(range(1, CreatePlan::WEEKS))->mapWithKeys(function (int $n) use ($monday) {
            $start = $monday->addWeeks($n - 1);

            return [$n => WeekRange::short($start, $start->addDays(6))];
        })->all();
    }

    /** Metas e base vêm do último plano do aluno, se existir */
    private function applyDefaults(): void
    {
        $previous = $this->previousPlan;

        $this->base = $previous ? 'duplicate' : 'blank';
        $this->startsOn = CarbonImmutable::today()->next(CarbonImmutable::MONDAY)->toDateString();
        $this->goals = [1 => null, 2 => null, 3 => null, 4 => null];

        if ($previous) {
            foreach ($previous->weeks as $week) {
                $this->goals[$week->number] = $week->goal_km;
            }

            // Começa na segunda seguinte ao fim do plano anterior
            $next = $previous->starts_on->copy()->addDays($previous->weeks->count() * 7);
            if ($next->isFuture() || $next->isToday()) {
                $this->startsOn = $next->toDateString();
            }
        }

        unset($this->firstMonday, $this->weekRanges);
        $this->title = $this->defaultTitle();
    }

    /** "Plano 09/11 a 06/12": o período real, sem prender o plano a um mês */
    private function defaultTitle(): string
    {
        $monday = $this->firstMonday;

        return $monday
            ? 'Plano '.WeekRange::period($monday, $monday->addDays(CreatePlan::WEEKS * 7 - 1))
            : 'Novo plano';
    }

    /** Metas finais, com o aumento percentual opcional */
    #[Computed]
    public function finalGoals(): array
    {
        return collect($this->goals)->map(function ($goal) {
            if ($goal === null || $goal === '') {
                return null;
            }

            $goal = (float) str_replace(',', '.', (string) $goal);

            return $this->increase ? round($goal * (1 + $this->increasePercent / 100), 1) : $goal;
        })->all();
    }

    protected function rules(): array
    {
        return [
            'studentId' => ['required', Rule::exists('trainer_student', 'student_id')->where('trainer_id', auth()->id())->where('status', 'active')],
            'title' => ['required', 'string', 'max:80'],
            'startsOn' => ['required', 'date'],
            'base' => ['required', 'in:duplicate,blank'],
            'goals.*' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'increasePercent' => ['integer', 'min:1', 'max:100'],
        ];
    }

    protected function validationAttributes(): array
    {
        return ['studentId' => 'aluno', 'title' => 'título', 'startsOn' => 'início', 'goals.*' => 'meta'];
    }

    public function create(CreatePlan $action): void
    {
        $this->validate();

        // O plano sempre começa numa segunda-feira
        $startsOn = $this->firstMonday;

        $plan = $action->handle(
            trainer: auth()->user(),
            student: User::findOrFail($this->studentId),
            title: $this->title,
            startsOn: $startsOn,
            base: $this->base === 'duplicate' ? $this->previousPlan : null,
            goals: $this->finalGoals,
        );

        session()->flash('saved', 'Plano criado como rascunho. Ajuste os dias e publique quando estiver pronto.');

        $this->redirectRoute('trainer.plans.edit', $plan, navigate: true);
    }

    public function render()
    {
        return view('livewire.trainer.plans.create');
    }
}
