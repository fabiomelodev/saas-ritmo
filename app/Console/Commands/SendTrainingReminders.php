<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\TrainingReminder;
use Illuminate\Console\Command;

/**
 * Lembretes dos alunos (agendados em routes/console.php):
 *   php artisan ritmo:lembretes hoje        → 07:00, "Treino de hoje: …"
 *   php artisan ritmo:lembretes pendentes   → 20:00, "Você ainda não registrou: …"
 */
class SendTrainingReminders extends Command
{
    protected $signature = 'ritmo:lembretes {tipo=hoje : hoje | pendentes}';

    protected $description = 'Envia os lembretes de treino do dia aos alunos';

    public function handle(): int
    {
        $kind = $this->argument('tipo') === 'pendentes' ? 'missed' : 'today';
        $sent = 0;

        User::where('role', Role::Student)->each(function (User $student) use ($kind, &$sent) {
            $plan = Plan::currentFor($student)?->loadCalendar();
            $week = $plan?->weeks->firstWhere('number', $plan->weekNumberFor(now()));
            $session = $week?->sessions->firstWhere('day_of_week', now()->dayOfWeekIso);

            if (! $session || $session->isRest()) {
                return;
            }

            // "pendentes" só para quem ainda não concluiu nem marcou "não fiz"
            if ($kind === 'missed' && in_array($session->log?->status, ['done', 'skipped'], true)) {
                return;
            }

            // Não repete o mesmo lembrete no dia
            $already = $student->notifications()
                ->where('type', TrainingReminder::class)
                ->whereDate('created_at', today())
                ->get()
                ->contains(fn ($n) => ($n->data['session_id'] ?? null) === $session->id && ($n->data['kind'] ?? null) === $kind);

            if ($already || ! $student->wantsNotification($kind === 'missed' ? 'missed_log' : 'daily_reminder')) {
                return;
            }

            $student->notify(new TrainingReminder($session, $kind));
            $sent++;
        });

        $this->info("Lembretes enviados: {$sent}");

        return self::SUCCESS;
    }
}
