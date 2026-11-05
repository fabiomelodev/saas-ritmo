<?php

namespace App\Livewire\Auth;

use App\Actions\RegisterStudentFromInvite;
use App\Models\TrainerProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Convite')]
class InviteRegister extends Component
{
    #[Locked]
    public string $code = '';

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public function mount(string $code): void
    {
        $this->code = $code;
    }

    #[Computed]
    public function invite(): ?TrainerProfile
    {
        return RegisterStudentFromInvite::findOpenInvite($this->code);
    }

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

    public function register(RegisterStudentFromInvite $action): void
    {
        abort_unless($this->invite, 404);

        $data = $this->validate();

        $student = $action->handle($this->invite, $data);

        Auth::login($student, remember: true);
        session()->regenerate();

        $this->redirectRoute('onboarding');
    }

    public function render()
    {
        return view('livewire.auth.invite-register');
    }
}
