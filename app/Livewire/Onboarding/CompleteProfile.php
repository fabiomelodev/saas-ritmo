<?php

namespace App\Livewire\Onboarding;

use App\Enums\Sex;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Etapa 2 do cadastro.
 * - Aluno: WhatsApp, data de nascimento, sexo + aceite dos termos
 * - Treinador: WhatsApp, CREF, cidade/UF, apresentação + aceite dos termos
 */
#[Layout('layouts.guest')]
#[Title('Complete seu perfil')]
class CompleteProfile extends Component
{
    public string $whatsapp = '';

    // Aluno
    public string $birth_date = '';

    public string $sex = '';

    // Treinador
    public string $cref = '';

    public string $city = '';

    public string $state = '';

    public string $bio = '';

    public bool $terms = false;

    public function mount(): void
    {
        $user = auth()->user();

        if ($user->hasCompletedProfile()) {
            $this->redirectRoute('home');

            return;
        }

        $this->whatsapp = (string) $user->whatsapp;
    }

    protected function rules(): array
    {
        $common = [
            'whatsapp' => ['required', 'string', 'min:10', 'max:20'],
            'terms' => ['accepted'],
        ];

        if (auth()->user()->isTrainer()) {
            return $common + [
                'cref' => ['nullable', 'string', 'max:30'],
                'city' => ['nullable', 'string', 'max:100'],
                'state' => ['nullable', 'string', 'size:2'],
                'bio' => ['nullable', 'string', 'max:500'],
            ];
        }

        return $common + [
            'birth_date' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'sex' => ['required', Rule::enum(Sex::class)],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'whatsapp' => 'WhatsApp',
            'birth_date' => 'data de nascimento',
            'sex' => 'sexo',
            'cref' => 'CREF',
            'city' => 'cidade',
            'state' => 'UF',
            'bio' => 'apresentação',
            'terms' => 'termos de uso',
        ];
    }

    public function save(): void
    {
        $data = $this->validate();
        $user = auth()->user();

        $user->fill([
            'whatsapp' => $data['whatsapp'],
            'terms_accepted_at' => now(),
            'profile_completed_at' => now(),
        ]);

        if ($user->isTrainer()) {
            $user->trainerProfile->update([
                'cref' => $data['cref'] ?: null,
                'city' => $data['city'] ?: null,
                'state' => $data['state'] ? strtoupper($data['state']) : null,
                'bio' => $data['bio'] ?: null,
            ]);
        } else {
            $user->fill([
                'birth_date' => $data['birth_date'],
                'sex' => $data['sex'],
            ]);
        }

        $user->save();

        $this->redirectRoute('home');
    }

    public function render()
    {
        return view('livewire.onboarding.complete-profile', [
            'isTrainer' => auth()->user()->isTrainer(),
        ]);
    }
}
