<?php

namespace App\Livewire\Auth;

use App\Actions\RegisterTrainer as RegisterTrainerAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Criar conta de treinador')]
class RegisterTrainer extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
        ];
    }

    protected function validationAttributes(): array
    {
        return ['name' => 'nome', 'email' => 'e-mail', 'password' => 'senha'];
    }

    public function register(RegisterTrainerAction $action): void
    {
        $data = $this->validate();

        $user = $action->handle($data);

        Auth::login($user, remember: true);
        session()->regenerate();

        $this->redirectRoute('onboarding');
    }

    public function render()
    {
        return view('livewire.auth.register-trainer');
    }
}
