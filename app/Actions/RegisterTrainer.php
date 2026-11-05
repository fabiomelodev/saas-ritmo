<?php

namespace App\Actions;

use App\Enums\Role;
use App\Models\Category;
use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterTrainer
{
    /**
     * Cria o usuário treinador e o perfil com o código de convite.
     *
     * @param  array{name: string, email: string, password?: ?string, google_id?: ?string}  $data
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'role' => Role::Trainer,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'] ?? null,
                'google_id' => $data['google_id'] ?? null,
                'email_verified_at' => isset($data['google_id']) ? now() : null,
            ]);

            TrainerProfile::create([
                'user_id' => $user->id,
                'invite_code' => TrainerProfile::generateInviteCode(),
            ]);

            Category::createDefaultsFor($user);

            return $user;
        });
    }
}
