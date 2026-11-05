<?php

namespace App\Livewire\Trainer\Plans;

use App\Models\Plan;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Planos')]
class Index extends Component
{
    #[Computed]
    public function plans()
    {
        return Plan::where('trainer_id', auth()->id())
            ->with(['student', 'weeks.sessions'])
            ->latest('starts_on')
            ->get();
    }

    public function render()
    {
        return view('livewire.trainer.plans.index');
    }
}
