<?php

namespace App\Livewire\Trainer;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Conta do treinador: perfil, link de convite, alunos e atalhos */
#[Layout('layouts.app')]
#[Title('Conta')]
class Account extends Component
{
    #[Computed]
    public function profile()
    {
        return auth()->user()->trainerProfile;
    }

    #[Computed]
    public function counts(): array
    {
        return [
            'active' => auth()->user()->students()->wherePivot('status', 'active')->count(),
            'archived' => auth()->user()->students()->wherePivot('status', 'archived')->count(),
        ];
    }

    /** Pausar/retomar convites: com pausa, o link mostra "convite indisponível" */
    public function toggleInvites(): void
    {
        $this->profile->update(['accepting_invites' => ! $this->profile->accepting_invites]);
        unset($this->profile);
    }

    /** Novo link: o antigo para de funcionar; quem já é aluno continua vinculado */
    public function regenerateInvite(): void
    {
        $this->profile->regenerateInviteCode();
        unset($this->profile);
        session()->now('saved', 'Novo link gerado. O antigo não funciona mais.');
    }

    public function render()
    {
        return view('livewire.trainer.account', ['user' => auth()->user()]);
    }
}
