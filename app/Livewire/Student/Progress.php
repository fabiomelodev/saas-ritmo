<?php

namespace App\Livewire\Student;

use App\Models\PersonalRecord;
use App\Models\Plan;
use App\Models\SessionLog;
use App\Support\Effort;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Evolução: volume semanal × meta, totais, histórico e recordes */
#[Layout('layouts.app')]
#[Title('Evolução')]
class Progress extends Component
{
    /** Distâncias oficiais (as mesmas da planilha) */
    public const DISTANCES = [
        '5 km' => 5,
        '10 km' => 10,
        '21 km (meia maratona)' => 21.0975,
        '42 km (maratona)' => 42.195,
    ];

    public bool $adding = false;

    public string $label = '5 km';

    public string $customLabel = '';

    public string $customDistance = '';

    public string $time = '';

    public string $achievedOn = '';

    #[Computed]
    public function plan(): ?Plan
    {
        $plan = Plan::currentFor(auth()->user())
            ?? auth()->user()->plans()->published()->withActiveLink()->latest('starts_on')->first();

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
            'currentWeek' => $this->plan?->weekNumberFor(now()),
            'weeks' => $this->plan?->weeks->map(fn ($w) => [
                'number' => $w->number,
                'range' => $w->rangeLabel(),
                'goal' => (float) $w->goal_km,
                'done' => (float) $w->sessions->sum(fn ($s) => $s->log?->status === 'done' ? (float) $s->log->distance_km : 0),
            ])->all() ?? [],
        ];
    }

    #[Computed]
    public function history()
    {
        return SessionLog::where('student_id', auth()->id())
            ->whereIn('status', ['done', 'skipped'])
            ->with(['session.week.plan', 'session.workoutSheet', 'replies'])
            ->latest('completed_at')
            ->limit(8)
            ->get();
    }

    #[Computed]
    public function records()
    {
        return auth()->user()->personalRecords()->orderBy('distance_km')->orderBy('time_seconds')->get();
    }

    protected function rules(): array
    {
        $custom = $this->label === 'other';

        return [
            'label' => ['required'],
            'customLabel' => [$custom ? 'required' : 'nullable', 'string', 'max:50'],
            'customDistance' => [$custom ? 'required' : 'nullable', 'regex:/^\d{1,3}([.,]\d{1,4})?$/'],
            'time' => ['required', 'regex:/^\d{1,3}(:\d{1,2}){1,2}$/'],
            'achievedOn' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    protected function messages(): array
    {
        return [
            'time.regex' => 'Use minutos:segundos (34:30) ou horas:minutos:segundos (1:15:30).',
            'customDistance.regex' => 'Use só números, ex.: 15 ou 7,5.',
        ];
    }

    protected function validationAttributes(): array
    {
        return ['customLabel' => 'nome da prova', 'customDistance' => 'distância', 'time' => 'tempo', 'achievedOn' => 'data'];
    }

    public function addRecord(): void
    {
        $this->validate();

        $distance = $this->label === 'other'
            ? Effort::parseKm($this->customDistance)
            : (self::DISTANCES[$this->label] ?? null);

        abort_unless($distance, 422);

        PersonalRecord::create([
            'student_id' => auth()->id(),
            'label' => $this->label === 'other' ? $this->customLabel : str($this->label)->before(' (')->toString(),
            'distance_km' => $distance,
            'time_seconds' => Effort::parseDuration($this->time),
            'achieved_on' => $this->achievedOn ?: null,
        ]);

        $this->reset('adding', 'customLabel', 'customDistance', 'time', 'achievedOn');
        $this->label = '5 km';
        unset($this->records);
    }

    public function deleteRecord(int $id): void
    {
        auth()->user()->personalRecords()->whereKey($id)->delete();
        unset($this->records);
    }

    public function render()
    {
        return view('livewire.student.progress', ['distances' => self::DISTANCES]);
    }
}
