<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Recuperar senha')]
class ForgotPassword extends Component
{
    #[Validate('required|email', as: 'e-mail')]
    public string $email = '';

    public bool $sent = false;

    public function send(): void
    {
        $this->validate();

        // Resposta igual exista ou não a conta (não revela quem é cadastrado)
        Password::sendResetLink(['email' => $this->email]);

        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
