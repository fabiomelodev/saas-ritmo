<?php

namespace App\Actions;

use App\Enums\Role;
use App\Models\TrainerProfile;
use App\Models\User;
use App\Notifications\StudentJoined;
use Illuminate\Support\Facades\DB;

class RegisterStudentFromInvite
{
    /**
     * Cria o aluno e já o vincula ao treinador dono do convite.
     *
     * @param  array{name: string, email: string, password?: ?string, google_id?: ?string}  $data
     */
    public function handle(TrainerProfile $invite, array $data): User
    {
        $student = DB::transaction(function () use ($invite, $data) {
            $student = User::create([
                'role' => Role::Student,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'] ?? null,
                'google_id' => $data['google_id'] ?? null,
                'email_verified_at' => isset($data['google_id']) ? now() : null,
            ]);

            $invite->user->students()->attach($student->id, ['status' => 'active']);

            return $student;
        });

        $invite->user->notify(new StudentJoined($student));

        return $student;
    }

    /** Convite válido = código existe e o treinador está aceitando alunos */
    public static function findOpenInvite(string $code): ?TrainerProfile
    {
        return TrainerProfile::with('user')
            ->where('invite_code', $code)
            ->where('accepting_invites', true)
            ->first();
    }
}
