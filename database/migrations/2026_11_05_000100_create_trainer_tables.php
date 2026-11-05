<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('cref', 30)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 2)->nullable();
            $table->text('bio')->nullable();
            $table->string('invite_code', 16)->unique();
            $table->boolean('accepting_invites')->default(true);
            $table->timestamps();
        });

        // Vínculo treinador ↔ aluno
        Schema::create('trainer_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('active'); // active | archived
            $table->string('goal')->nullable();   // ex.: Fortalecer e ganhar condicionamento
            $table->string('level', 30)->nullable(); // ex.: Iniciante
            $table->text('private_notes')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['trainer_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_student');
        Schema::dropIfExists('trainer_profiles');
    }
};
