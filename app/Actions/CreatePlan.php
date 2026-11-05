<?php

namespace App\Actions;

use App\Enums\PlanStatus;
use App\Enums\SessionType;
use App\Models\Plan;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class CreatePlan
{
    public const WEEKS = 4;

    /**
     * Cria um plano em rascunho com 4 semanas × 7 dias.
     *
     * @param  ?Plan  $base  plano a duplicar (sessões, fichas e observações); null = em branco (tudo descanso)
     * @param  array<int, float|int|null>  $goals  meta de km por semana [1 => 15, 2 => 15, …]
     */
    public function handle(User $trainer, User $student, string $title, CarbonInterface $startsOn, ?Plan $base, array $goals): Plan
    {
        // As sessões contam a partir da segunda-feira (day_of_week ISO 1..7).
        // Com o locale pt_BR o Carbon começa a semana no domingo, então fixamos aqui.
        $startsOn = $startsOn->copy()->startOfWeek(CarbonInterface::MONDAY);

        return DB::transaction(function () use ($trainer, $student, $title, $startsOn, $base, $goals) {
            $plan = Plan::create([
                'trainer_id' => $trainer->id,
                'student_id' => $student->id,
                'title' => $title,
                'starts_on' => $startsOn->toDateString(),
                'status' => PlanStatus::Draft,
            ]);

            $base?->load('weeks.sessions');

            for ($number = 1; $number <= self::WEEKS; $number++) {
                $week = $plan->weeks()->create([
                    'number' => $number,
                    'goal_km' => $goals[$number] ?? null,
                ]);

                $baseSessions = $base?->weeks->firstWhere('number', $number)?->sessions;

                for ($day = 1; $day <= 7; $day++) {
                    $source = $baseSessions?->firstWhere('day_of_week', $day);

                    $week->sessions()->create($source
                        ? $source->only(['day_of_week', 'type', 'title', 'distance_km', 'duration', 'pace', 'pace_unit', 'notes', 'workout_sheet_id'])
                        : ['day_of_week' => $day, 'type' => SessionType::Rest, 'title' => 'Descanso']);
                }
            }

            return $plan;
        });
    }
}
