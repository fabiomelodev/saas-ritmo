<?php

namespace App\Livewire\Trainer;

use App\Models\SessionLog;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Meus alunos')]
class Students extends Component
{
    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** active | archived */
    #[Url(as: 'mostrar', except: 'active')]
    public string $show = 'active';

    #[Computed]
    public function profile()
    {
        return auth()->user()->trainerProfile;
    }

    #[Computed]
    public function students()
    {
        return auth()->user()
            ->students()
            ->wherePivot('status', $this->show === 'archived' ? 'archived' : 'active')
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function archivedCount(): int
    {
        return auth()->user()->students()->wherePivot('status', 'archived')->count();
    }

    /** Treinos registrados hoje pelos alunos */
    #[Computed]
    public function logsToday(): int
    {
        return SessionLog::forTrainer(auth()->id())->whereDate('completed_at', today())->count();
    }

    public function render()
    {
        return view('livewire.trainer.students');
    }
}
