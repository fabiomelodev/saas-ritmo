<?php

namespace App\Models;

use App\Support\WeekRange;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanWeek extends Model
{
    protected $fillable = ['plan_id', 'number', 'goal_km'];

    protected function casts(): array
    {
        return ['goal_km' => 'float'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PlanSession::class)->orderBy('day_of_week');
    }

    /** Soma das distâncias planejadas (o que a planilha calculava na aba Resumo) */
    public function plannedKm(): float
    {
        return (float) $this->sessions->sum('distance_km');
    }

    /** Segunda-feira desta semana (o plano sempre começa numa segunda) */
    public function startsOn(): CarbonInterface
    {
        return $this->plan->starts_on->copy()->addDays(($this->number - 1) * 7);
    }

    public function endsOn(): CarbonInterface
    {
        return $this->startsOn()->addDays(6);
    }

    /** "09–15/nov" */
    public function rangeLabel(): string
    {
        return WeekRange::short($this->startsOn(), $this->endsOn());
    }
}
