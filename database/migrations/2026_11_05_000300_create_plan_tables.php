<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');            // Plano de novembro
            $table->date('starts_on');          // segunda-feira da semana 1
            $table->string('status', 20)->default('draft'); // draft | published
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'starts_on']);
        });

        Schema::create('plan_weeks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('number'); // 1..4
            $table->decimal('goal_km', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'number']);
        });

        Schema::create('plan_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_week_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 1 = segunda … 7 = domingo
            $table->string('type', 20);                 // run | strength | rest
            $table->string('title');                    // Corrida Z2, Regenerativo…
            $table->decimal('distance_km', 5, 2)->nullable();
            $table->string('duration')->nullable();     // texto livre: 90 min, 2' cada velocidade
            $table->string('pace')->nullable();         // 7, 8:20, 9 e 7
            $table->string('pace_unit', 10)->nullable();// kmh | minkm
            $table->text('notes')->nullable();
            $table->foreignId('workout_sheet_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['plan_week_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_sessions');
        Schema::dropIfExists('plan_weeks');
        Schema::dropIfExists('plans');
    }
};
