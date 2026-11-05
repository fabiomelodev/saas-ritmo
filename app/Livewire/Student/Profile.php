<?php

namespace App\Livewire\Student;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Perfil do aluno: dados, treinador e atalhos de conta */
#[Layout('layouts.app')]
#[Title('Perfil')]
class Profile extends Component
{
    public function render()
    {
        return view('livewire.student.profile', [
            'user' => auth()->user(),
            'trainer' => auth()->user()->currentTrainer(),
        ]);
    }
}
