<?php

namespace App\Livewire\Trainer\Plans;

use App\Enums\PlanStatus;
use App\Enums\SessionType;
use App\Livewire\Concerns\ChecksTrainerOwnership;
use App\Models\Plan;
use App\Models\PlanSession;
use App\Models\PlanWeek;
use App\Notifications\PlanPublished;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Editor do plano mensal: grade 4 semanas × 7 dias + painel da sessão selecionada.
 */
#[Layout('layouts.app')]
#[Title('Editor do plano')]
class Edit extends Component
{
    use ChecksTrainerOwnership;

    #[Locked]
    public int $planId;

    #[Locked]
    public ?int $selectedId = null;

    public string $planTitle = '';

    /** Data de início (editável só em rascunho) */
    public string $startsOn = '';

    /** @var array<int, mixed> metas por id da semana */
    public array $goals = [];

    // Formulário da sessão selecionada
    public string $type = 'rest';

    public string $title = '';

    public ?string $distance_km = null;

    public ?string $duration = null;

    public ?string $pace = null;

    public string $pace_unit = 'kmh';

    public ?string $notes = null;

    /** id da ficha como texto ('' = nenhuma), como vem do <select> */
    public string $workout_sheet_id = '';

    public function mount(Plan $plan): void
    {
        $this->ensureOwns($plan);

        $this->planId = $plan->id;
        $this->planTitle = $plan->title;
        $this->startsOn = $plan->starts_on->toDateString();
        $this->goals = $plan->weeks->mapWithKeys(fn (PlanWeek $w) => [$w->id => $w->goal_km])->all();

        // Abre no primeiro dia de treino
        $first = $plan->sessions()->where('type', '!=', SessionType::Rest->value)->orderBy('plan_week_id')->orderBy('day_of_week')->first()
            ?? $plan->sessions()->first();

        if ($first) {
            $this->select($first->id);
        }
    }

    #[Computed]
    public function plan(): Plan
    {
        return Plan::with(['student', 'weeks.sessions.workoutSheet'])->findOrFail($this->planId);
    }

    #[Computed]
    public function sheets()
    {
        return auth()->user()->workoutSheets()->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function selected(): ?PlanSession
    {
        return $this->selectedId
            ? $this->plan->weeks->flatMap->sessions->firstWhere('id', $this->selectedId)
            : null;
    }

    public function select(int $sessionId): void
    {
        $session = $this->findSession($sessionId);

        $this->selectedId = $session->id;
        $this->type = $session->type->value;
        $this->title = $session->title;
        $this->distance_km = $session->distance_km !== null ? $this->formatNumber($session->distance_km) : null;
        $this->duration = $session->duration;
        $this->pace = $session->pace;
        $this->pace_unit = $session->pace_unit ?: 'kmh';
        $this->notes = $session->notes;
        $this->workout_sheet_id = (string) $session->workout_sheet_id;

        $this->resetValidation();
        unset($this->selected);
    }

    /** Ao trocar o tipo, sugere um título coerente */
    public function updatedType(string $value): void
    {
        if ($value === SessionType::Rest->value) {
            $this->title = 'Descanso';
        } elseif ($value === SessionType::Strength->value) {
            $sheet = $this->sheets->firstWhere('id', (int) $this->workout_sheet_id) ?? $this->sheets->first();
            $this->workout_sheet_id = (string) $sheet?->id;
            $this->title = $sheet ? $sheet->name.' e mobilidade' : 'Força e mobilidade';
        } elseif ($this->title === 'Descanso' || str_contains($this->title, 'mobilidade')) {
            $this->title = '';
        }
    }

    public function updatedWorkoutSheetId($value): void
    {
        $sheet = $this->sheets->firstWhere('id', (int) $value);

        if ($sheet && $this->type === SessionType::Strength->value) {
            $this->title = $sheet->name.' e mobilidade';
        }
    }

    /** Meta da semana salva ao sair do campo */
    public function updatedGoals($value, $weekId): void
    {
        $week = $this->plan->weeks->firstWhere('id', (int) $weekId);
        abort_unless($week, 404);

        $value = $value === '' || $value === null ? null : (float) str_replace(',', '.', (string) $value);
        $week->update(['goal_km' => $value]);

        unset($this->plan);
    }

    protected function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(SessionType::class)],
            'title' => ['required', 'string', 'max:80'],
            'distance_km' => ['nullable', 'regex:/^\d{1,3}([.,]\d{1,2})?$/'],
            'duration' => ['nullable', 'string', 'max:60'],
            'pace' => ['nullable', 'string', 'max:30'],
            'pace_unit' => ['required', 'in:kmh,minkm'],
            'notes' => ['nullable', 'string', 'max:500'],
            'workout_sheet_id' => [
                Rule::requiredIf($this->type === SessionType::Strength->value),
                'nullable',
                Rule::exists('workout_sheets', 'id')->where('trainer_id', auth()->id()),
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'distance_km.regex' => 'Use só números, ex.: 5 ou 6,5.',
            'workout_sheet_id.required' => 'Escolha a ficha de força.',
        ];
    }

    protected function validationAttributes(): array
    {
        return ['title' => 'treino', 'distance_km' => 'distância', 'duration' => 'tempo', 'pace' => 'ritmo', 'notes' => 'observações'];
    }

    public function saveSession(): void
    {
        $this->validate();

        $this->findSession($this->selectedId)->update($this->sessionAttributes());

        unset($this->plan, $this->selected);
        session()->now('saved', 'Sessão salva.');
    }

    /** Copia a sessão para o mesmo dia da semana nas outras semanas */
    public function copyToAllWeeks(): void
    {
        $this->validate();

        $session = $this->findSession($this->selectedId);
        $session->update($this->sessionAttributes());

        foreach ($this->plan->weeks as $week) {
            $week->sessions->firstWhere('day_of_week', $session->day_of_week)?->update($this->sessionAttributes());
        }

        unset($this->plan, $this->selected);
        session()->now('saved', 'Copiado para as '.$this->plan->weeks->count().' semanas.');
    }

    public function saveTitle(): void
    {
        $this->validateOnly('planTitle', ['planTitle' => ['required', 'string', 'max:80']]);
        $this->plan->update(['title' => $this->planTitle]);
        unset($this->plan);
    }

    /**
     * Muda o início do plano (só em rascunho). A data vira a segunda-feira da semana escolhida;
     * as sessões acompanham, porque as datas são calculadas a partir do início.
     */
    public function changeStartDate(): void
    {
        $plan = $this->plan;
        abort_if($plan->status === PlanStatus::Published, 403, 'Volte o plano para rascunho para mudar o início.');

        $this->validate(['startsOn' => ['required', 'date']], [], ['startsOn' => 'início']);

        if ($plan->hasLogs()) {
            $this->addError('startsOn', 'O aluno já registrou treinos deste plano; mudar o início desalinharia os registros.');

            return;
        }

        $monday = CarbonImmutable::parse($this->startsOn)->startOfWeek(CarbonImmutable::MONDAY);
        $weeks = $plan->weeks->count();

        $attributes = ['starts_on' => $monday->toDateString()];

        // Título ainda no padrão sugerido? Acompanha as novas datas.
        if ($plan->title === Plan::suggestedTitle($plan->starts_on, $weeks)) {
            $attributes['title'] = $this->planTitle = Plan::suggestedTitle($monday, $weeks);
        }

        $plan->update($attributes);
        $this->startsOn = $monday->toDateString();

        unset($this->plan, $this->selected);
        session()->now('saved', 'Início alterado para segunda, '.$monday->format('d/m').'.');
        $this->dispatch('start-date-changed');
    }

    /** Exclui o plano (só rascunho e sem treinos registrados pelo aluno) */
    public function deletePlan(): void
    {
        $plan = $this->plan;
        abort_if($plan->status === PlanStatus::Published, 403, 'Volte o plano para rascunho antes de excluir.');

        if ($plan->hasLogs()) {
            session()->now('error', 'Este plano tem treinos registrados pelo aluno. Para não apagar esse histórico, ele não pode ser excluído.');

            return;
        }

        $title = $plan->title;
        $plan->delete(); // semanas e sessões saem em cascata

        session()->flash('saved', 'Plano “'.$title.'” excluído.');
        $this->redirectRoute('trainer.plans.index', navigate: true);
    }

    public function publish(): void
    {
        $this->plan->publish();
        $this->plan->student->notify(new PlanPublished($this->plan->load('trainer')));
        unset($this->plan);
        session()->now('saved', 'Plano publicado. '.$this->plan->student->first_name.' já pode ver os treinos.');
    }

    public function unpublish(): void
    {
        $this->plan->update(['status' => PlanStatus::Draft]);
        unset($this->plan);
    }

    private function sessionAttributes(): array
    {
        $isRun = $this->type === SessionType::Run->value;
        $isStrength = $this->type === SessionType::Strength->value;

        return [
            'type' => $this->type,
            'title' => $this->title,
            'distance_km' => $isRun && filled($this->distance_km) ? (float) str_replace(',', '.', $this->distance_km) : null,
            'duration' => $isRun ? ($this->duration ?: null) : null,
            'pace' => $isRun ? ($this->pace ?: null) : null,
            'pace_unit' => $isRun && filled($this->pace) ? $this->pace_unit : null,
            'notes' => $this->type !== SessionType::Rest->value ? ($this->notes ?: null) : null,
            'workout_sheet_id' => $isStrength && $this->workout_sheet_id !== '' ? (int) $this->workout_sheet_id : null,
        ];
    }

    private function findSession(?int $id): PlanSession
    {
        $session = PlanSession::with('week.plan')->findOrFail($id);
        abort_unless($session->week->plan_id === $this->planId, 404);

        return $session;
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', ''), '0'), ',');
    }

    public function render()
    {
        return view('livewire.trainer.plans.edit', [
            'days' => ['SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SÁB', 'DOM'],
            'fullDays' => ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'],
            'fmt' => fn ($v) => $this->formatNumber((float) $v),
        ]);
    }
}
