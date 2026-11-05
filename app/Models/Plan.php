<?php

namespace App\Models;

use App\Enums\PlanStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Plan extends Model
{
    protected $fillable = ['trainer_id', 'student_id', 'title', 'starts_on', 'status', 'published_at'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'published_at' => 'datetime',
            'status' => PlanStatus::class,
        ];
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function weeks(): HasMany
    {
        return $this->hasMany(PlanWeek::class)->orderBy('number');
    }

    public function sessions(): HasManyThrough
    {
        return $this->hasManyThrough(PlanSession::class, PlanWeek::class);
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', PlanStatus::Published);
    }

    /** Só planos de vínculos ativos: aluno arquivado deixa de ver o plano */
    public function scopeWithActiveLink(Builder $query): void
    {
        $query->whereExists(fn ($q) => $q->selectRaw('1')
            ->from('trainer_student')
            ->whereColumn('trainer_student.trainer_id', 'plans.trainer_id')
            ->whereColumn('trainer_student.student_id', 'plans.student_id')
            ->where('trainer_student.status', 'active'));
    }

    public function hasActiveLink(): bool
    {
        return static::whereKey($this->id)->withActiveLink()->exists();
    }

    public function endsOn(): \Carbon\CarbonInterface
    {
        $weeks = $this->relationLoaded('weeks') ? $this->weeks->count() : $this->weeks()->count();

        return $this->starts_on->copy()->addDays($weeks * 7 - 1);
    }

    /** "09/11 a 06/12" */
    public function periodLabel(): string
    {
        return \App\Support\WeekRange::period($this->starts_on, $this->endsOn());
    }

    /**
     * Carrega semanas, sessões, registros e fichas e liga as relações inversas
     * (assim date()/status() não fazem uma consulta por sessão).
     */
    public function loadCalendar(): static
    {
        $this->loadMissing(['weeks.sessions.log', 'weeks.sessions.workoutSheet']);

        foreach ($this->weeks as $week) {
            $week->setRelation('plan', $this);
            $week->sessions->each(fn (PlanSession $s) => $s->setRelation('week', $week));
        }

        return $this;
    }

    /** Plano publicado do aluno que cobre a data (o mais recente, se houver sobreposição) */
    public static function currentFor(User $student, ?\Carbon\CarbonInterface $date = null): ?self
    {
        $date ??= now();

        return static::where('student_id', $student->id)
            ->published()
            ->withActiveLink()
            ->whereDate('starts_on', '<=', $date)
            ->latest('starts_on')
            ->with('weeks')
            ->get()
            ->first(fn (self $plan) => $date->copy()->startOfDay()->lte($plan->starts_on->copy()->addDays($plan->weeks->count() * 7 - 1)));
    }

    /** Número da semana (1..n) para a data, ou null fora do plano */
    public function weekNumberFor(\Carbon\CarbonInterface $date): ?int
    {
        $days = (int) $this->starts_on->copy()->startOfDay()->diffInDays($date->copy()->startOfDay(), false);
        $number = intdiv($days, 7) + 1;

        return $days >= 0 && $number <= $this->weeks->count() ? $number : null;
    }

    public function publish(): void
    {
        $this->update(['status' => PlanStatus::Published, 'published_at' => now()]);
    }
}
