<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // O que o aluno fez em cada sessão
        Schema::create('session_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_session_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('done'); // done | skipped
            $table->decimal('distance_km', 5, 2)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedTinyInteger('rpe')->nullable(); // PSE 1..10
            $table->text('comment')->nullable();
            $table->json('checked_items')->nullable();      // exercícios marcados na ficha
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('seen_by_trainer_at')->nullable();
            $table->timestamps();
        });

        Schema::create('log_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('personal_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('label', 50);            // 5 km, 10 km, Meia Maratona
            $table->decimal('distance_km', 7, 4);   // 21.0975
            $table->unsignedInteger('time_seconds');
            $table->date('achieved_on')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_records');
        Schema::dropIfExists('log_replies');
        Schema::dropIfExists('session_logs');
    }
};
