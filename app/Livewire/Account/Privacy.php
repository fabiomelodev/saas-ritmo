<?php

namespace App\Livewire\Account;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Privacidade e termos: dados guardados, documentos, baixar e excluir (LGPD) */
#[Layout('layouts.app')]
#[Title('Privacidade e termos')]
class Privacy extends Component
{
    public bool $deleting = false;

    public string $confirmation = '';

    public string $password = '';

    public function deleteAccount(): void
    {
        $user = auth()->user();

        $this->validate([
            'confirmation' => ['required', 'in:EXCLUIR'],
            'password' => $user->hasPassword() ? ['required', 'current_password'] : ['nullable'],
        ], [
            'confirmation.in' => 'Digite EXCLUIR, em maiúsculas, para confirmar.',
        ], ['confirmation' => 'confirmação', 'password' => 'senha']);

        // Fotos de exercícios do treinador saem do disco junto com a conta
        if ($user->isTrainer()) {
            $user->exercises()->with('images')->get()
                ->flatMap->images
                ->each(fn ($image) => Storage::disk('public')->delete($image->path));
        }

        Auth::logout();
        $user->notifications()->delete();
        $user->pushSubscriptions()->delete(); // relação polimórfica: não sai em cascata
        $user->delete(); // planos, registros, vínculos e recordes saem em cascata

        session()->invalidate();
        session()->regenerateToken();
        session()->flash('status', 'Sua conta foi excluída.');

        $this->redirectRoute('login');
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.account.privacy', [
            'user' => $user,
            'back' => $user->isTrainer() ? route('trainer.account') : route('student.profile'),
        ]);
    }
}
