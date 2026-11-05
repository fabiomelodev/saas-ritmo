<?php

namespace App\Livewire\Account;

use App\Enums\Sex;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Editar dados (aluno) / Editar perfil (treinador) + senha */
#[Layout('layouts.app')]
#[Title('Editar dados')]
class EditProfile extends Component
{
    public string $name = '';

    public string $email = '';

    public string $whatsapp = '';

    // Aluno
    public string $birth_date = '';

    public string $sex = '';

    // Treinador
    public string $cref = '';

    public string $city = '';

    public string $state = '';

    public string $bio = '';

    // Senha
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = auth()->user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->whatsapp = (string) $user->whatsapp;
        $this->birth_date = (string) $user->birth_date?->toDateString();
        $this->sex = (string) $user->sex?->value;

        if ($profile = $user->trainerProfile) {
            $this->cref = (string) $profile->cref;
            $this->city = (string) $profile->city;
            $this->state = (string) $profile->state;
            $this->bio = (string) $profile->bio;
        }
    }

    protected function rules(): array
    {
        $user = auth()->user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'whatsapp' => ['required', 'string', 'min:10', 'max:20'],
        ];

        return $user->isTrainer()
            ? $rules + [
                'cref' => ['nullable', 'string', 'max:30'],
                'city' => ['nullable', 'string', 'max:100'],
                'state' => ['nullable', 'string', 'size:2'],
                'bio' => ['nullable', 'string', 'max:500'],
            ]
            : $rules + [
                'birth_date' => ['required', 'date', 'before:today', 'after:1900-01-01'],
                'sex' => ['required', Rule::enum(Sex::class)],
            ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => 'nome', 'email' => 'e-mail', 'whatsapp' => 'WhatsApp', 'birth_date' => 'data de nascimento',
            'sex' => 'sexo', 'city' => 'cidade', 'state' => 'UF', 'bio' => 'apresentação',
            'current_password' => 'senha atual', 'password' => 'nova senha',
        ];
    }

    public function save(): void
    {
        $data = $this->validate();
        $user = auth()->user();

        $emailChanged = $data['email'] !== $user->email;

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'whatsapp' => $data['whatsapp'],
        ]);

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        if ($user->isTrainer()) {
            $user->trainerProfile->update([
                'cref' => $data['cref'] ?: null,
                'city' => $data['city'] ?: null,
                'state' => $data['state'] ? strtoupper($data['state']) : null,
                'bio' => $data['bio'] ?: null,
            ]);
        } else {
            $user->fill(['birth_date' => $data['birth_date'], 'sex' => $data['sex']]);
        }

        $user->save();

        session()->flash('saved', 'Dados salvos.');
        $this->redirectRoute($user->isTrainer() ? 'trainer.account' : 'student.profile', navigate: true);
    }

    /** Alterar senha (ou criar, para quem entrou só com Google) */
    public function updatePassword(): void
    {
        $user = auth()->user();

        $this->validate([
            'current_password' => $user->hasPassword() ? ['required', 'current_password'] : ['nullable'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user->forceFill(['password' => Hash::make($this->password)])->save();

        $this->reset('current_password', 'password', 'password_confirmation');
        session()->now('saved', $user->wasChanged('password') ? 'Senha atualizada.' : 'Senha salva.');
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.account.edit-profile', [
            'isTrainer' => $user->isTrainer(),
            'hasPassword' => $user->hasPassword(),
            'hasGoogle' => filled($user->google_id),
        ])->title($user->isTrainer() ? 'Editar perfil' : 'Editar dados');
    }
}
