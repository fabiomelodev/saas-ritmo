<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\Sex;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, Notifiable;

    protected $fillable = [
        'role',
        'name',
        'email',
        'email_verified_at',
        'password',
        'google_id',
        'whatsapp',
        'birth_date',
        'sex',
        'photo_path',
        'pace_unit',
        'notification_preferences',
        'terms_accepted_at',
        'profile_completed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'sex' => Sex::class,
            'birth_date' => 'date',
            'email_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'profile_completed_at' => 'datetime',
            'notification_preferences' => 'array',
            'password' => 'hashed',
        ];
    }

    public function isTrainer(): bool
    {
        return $this->role === Role::Trainer;
    }

    public function isStudent(): bool
    {
        return $this->role === Role::Student;
    }

    /** Avisos ficam ligados até o usuário desligar */
    public function wantsNotification(string $key): bool
    {
        return (bool) (($this->notification_preferences ?? [])[$key] ?? true);
    }

    public function hasPassword(): bool
    {
        return filled($this->getAuthPassword());
    }

    public function hasCompletedProfile(): bool
    {
        return $this->profile_completed_at !== null;
    }

    /** Iniciais para o avatar: "Camile Vitória" → "CV" */
    protected function initials(): Attribute
    {
        return Attribute::get(function () {
            $parts = preg_split('/\s+/', trim($this->name));
            $first = $parts[0] ?? '';
            $last = count($parts) > 1 ? end($parts) : '';

            return Str::upper(Str::substr($first, 0, 1).Str::substr($last, 0, 1));
        });
    }

    protected function firstName(): Attribute
    {
        return Attribute::get(fn () => Str::before(trim($this->name), ' '));
    }

    protected function age(): Attribute
    {
        return Attribute::get(fn () => $this->birth_date?->age);
    }

    /* ---------- Treinador ---------- */

    public function trainerProfile(): HasOne
    {
        return $this->hasOne(TrainerProfile::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'trainer_student', 'trainer_id', 'student_id')
            ->using(TrainerStudent::class)
            ->withPivot(['id', 'status', 'goal', 'level', 'private_notes', 'archived_at'])
            ->withTimestamps();
    }

    public function activeStudents(): BelongsToMany
    {
        return $this->students()->wherePivot('status', 'active');
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class, 'trainer_id');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'trainer_id')->orderBy('position')->orderBy('name');
    }

    public function workoutSheets(): HasMany
    {
        return $this->hasMany(WorkoutSheet::class, 'trainer_id');
    }

    /** Registros novos (não vistos) dos alunos — badge do menu "Registros" */
    public function unseenLogsCount(): int
    {
        return SessionLog::forTrainer($this->id)->unseen()->count();
    }

    /* ---------- Aluno ---------- */

    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'trainer_student', 'student_id', 'trainer_id')
            ->using(TrainerStudent::class)
            ->withPivot(['id', 'status', 'goal', 'level', 'private_notes', 'archived_at'])
            ->withTimestamps();
    }

    /** Treinador ativo do aluno (um por vez no MVP) */
    public function currentTrainer(): ?User
    {
        return $this->trainers()->wherePivot('status', 'active')->first();
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class, 'student_id');
    }

    public function sessionLogs(): HasMany
    {
        return $this->hasMany(SessionLog::class, 'student_id');
    }

    public function personalRecords(): HasMany
    {
        return $this->hasMany(PersonalRecord::class, 'student_id');
    }
}
