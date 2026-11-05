<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Enums\Sex;
use App\Models\Category;
use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Padrão: aluno com perfil completo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role' => Role::Student,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'whatsapp' => '(11) 98888-7777',
            'birth_date' => fake()->dateTimeBetween('-50 years', '-18 years'),
            'sex' => fake()->randomElement(Sex::cases()),
            'terms_accepted_at' => now(),
            'profile_completed_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    /** Treinador com perfil e código de convite */
    public function trainer(): static
    {
        return $this->state(fn () => [
            'role' => Role::Trainer,
            'birth_date' => null,
            'sex' => null,
        ])->afterCreating(function (User $user) {
            TrainerProfile::create([
                'user_id' => $user->id,
                'invite_code' => TrainerProfile::generateInviteCode(),
            ]);

            Category::createDefaultsFor($user);
        });
    }

    /** Ainda não concluiu a etapa 2 do cadastro */
    public function incompleteProfile(): static
    {
        return $this->state(fn () => [
            'whatsapp' => null,
            'birth_date' => null,
            'sex' => null,
            'terms_accepted_at' => null,
            'profile_completed_at' => null,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
