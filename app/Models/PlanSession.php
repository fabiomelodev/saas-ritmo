<?php

namespace App\Models;

use App\Enums\PlanStatus;
use App\Enums\SessionType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PlanSession extends Model
{
    protected $fillable = [
        'plan_week_id',
        'day_of_week',
        'type',
        'title',
        'distance_km',
        'duration',
        'pace',
        'pace_unit',
        'notes',
        'workout_sheet_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => SessionType::class,
            'distance_km' => 'float',
        ];
    }

    public function week(): BelongsTo
    {
        return $this->belongsTo(PlanWeek::class, 'plan_week_id');
    }

    public function workoutSheet(): BelongsTo
    {
        return $this->belongsTo(WorkoutSheet::class);
    }

    public function log(): HasOne
    {
        return $this->hasOne(SessionLog::class);
    }

    /** Data real da sessão: início do plano + semana + dia */
    public function date(): CarbonInterface
    {
        $week = $this->week;

        return $week->plan->starts_on->copy()
            ->addDays(($week->number - 1) * 7 + ($this->day_of_week - 1));
    }

    public function isRest(): bool
    {
        return $this->type === SessionType::Rest;
    }

    /**
     * Situação para o aluno:
     * done | skipped | in_progress (ficha começada) | today | pending (passou sem registro) | upcoming | rest
     */
    public function status(?CarbonInterface $today = null): string
    {
        if ($this->isRest()) {
            return 'rest';
        }

        $log = $this->log;

        if ($log && in_array($log->status, ['done', 'skipped'], true)) {
            return $log->status;
        }

        $today = ($today ?? now())->copy()->startOfDay();
        $date = $this->date()->startOfDay();

        return match (true) {
            $date->equalTo($today) => $log ? 'in_progress' : 'today',
            $date->lessThan($today) => $log ? 'in_progress' : 'pending',
            default => 'upcoming',
        };
    }

    /** O aluno só registra o que já chegou (hoje ou dias anteriores) */
    public function canBeLogged(?CarbonInterface $today = null): bool
    {
        return ! $this->isRest() && $this->date()->startOfDay()->lessThanOrEqualTo(($today ?? now())->startOfDay());
    }

    /** Sessão de um plano publicado do aluno */
    public function belongsToStudent(User $student): bool
    {
        $plan = $this->week->plan;

        return (int) $plan->student_id === (int) $student->id
            && $plan->status === PlanStatus::Published
            && $plan->hasActiveLink();
    }

    public function distanceLabel(): ?string
    {
        return $this->distance_km ? rtrim(rtrim(number_format($this->distance_km, 2, ',', ''), '0'), ',').' km' : null;
    }

    /** "5 km · 7 km/h", "90 min", "Ficha A"… */
    public function summary(): string
    {
        return match ($this->type) {
            SessionType::Run => collect([$this->distanceLabel(), $this->paceLabel() ?? $this->duration])->filter()->join(' · ') ?: 'Livre',
            SessionType::Strength => $this->workoutSheet?->name ?? 'Força',
            SessionType::Rest => 'Recuperação',
        };
    }

    public function paceLabel(): ?string
    {
        if (blank($this->pace)) {
            return null;
        }

        return $this->pace.' '.($this->pace_unit === 'minkm' ? 'min/km' : 'km/h');
    }
}
