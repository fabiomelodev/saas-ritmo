<?php

namespace App\Support;

use App\Models\User;

/** Itens do menu (barra inferior no celular, barra superior do treinador no desktop) */
class Navigation
{
    /**
     * @return array<int, array{label: string, icon: string, route: ?string, active: array<int, string>, badge?: int}>
     */
    public static function for(User $user): array
    {
        if ($user->isTrainer()) {
            return [
                ['label' => 'Alunos', 'icon' => 'users', 'route' => 'trainer.students', 'active' => ['trainer.students', 'trainer.students.*']],
                ['label' => 'Registros', 'icon' => 'chat', 'route' => 'trainer.logs', 'active' => ['trainer.logs'], 'badge' => $user->unseenLogsCount()],
                ['label' => 'Planos', 'icon' => 'calendar', 'route' => 'trainer.plans.index', 'active' => ['trainer.plans.*']],
                ['label' => 'Fichas', 'icon' => 'dumbbell', 'route' => 'trainer.sheets.index', 'active' => ['trainer.sheets.*', 'trainer.exercises.*', 'trainer.categories']],
                ['label' => 'Conta', 'icon' => 'user', 'route' => 'trainer.account', 'active' => ['trainer.account', 'account.*', 'notifications']],
            ];
        }

        return [
            ['label' => 'Hoje', 'icon' => 'home', 'route' => 'student.home', 'active' => ['student.home']],
            ['label' => 'Plano', 'icon' => 'calendar', 'route' => 'student.plan', 'active' => ['student.plan', 'student.session', 'student.exercise']],
            ['label' => 'Evolução', 'icon' => 'chart', 'route' => 'student.progress', 'active' => ['student.progress']],
            ['label' => 'Perfil', 'icon' => 'user', 'route' => 'student.profile', 'active' => ['student.profile', 'account.*', 'notifications']],
        ];
    }

    public static function isActive(array $item): bool
    {
        return $item['active'] && request()->routeIs(...$item['active']);
    }
}
