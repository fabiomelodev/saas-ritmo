<?php

namespace App\Livewire\Concerns;

use App\Models\PlanSession;

trait LoadsStudentSession
{
    /** Sessão de um plano publicado do aluno logado; qualquer outra vira 404 */
    protected function loadStudentSession(int $id): PlanSession
    {
        $session = PlanSession::with(['week.plan.trainer', 'log', 'workoutSheet'])->findOrFail($id);

        abort_unless($session->belongsToStudent(auth()->user()), 404);

        return $session;
    }
}
