<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Categorias da biblioteca: cada treinador tem a própria lista
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 50);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['trainer_id', 'name']);
        });

        // Biblioteca de exercícios do treinador (mídia vale para todas as fichas)
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete(); // excluir a categoria não apaga o exercício
            $table->string('name');
            $table->text('instructions')->nullable();
            $table->string('youtube_url')->nullable();
            $table->unsignedInteger('video_start_seconds')->nullable();
            $table->timestamps();
        });

        Schema::create('exercise_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('caption')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        // Fichas de força (Treino A, Treino B…)
        Schema::create('workout_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->string('name'); // Treino A
            $table->timestamps();
        });

        // Blocos: na planilha, pares de exercícios dividem as mesmas séries
        Schema::create('sheet_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_sheet_id')->constrained()->cascadeOnDelete();
            $table->string('section', 20)->default('strength'); // strength | mobility
            $table->unsignedTinyInteger('sets')->nullable();    // 3
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('block_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sheet_block_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->string('prescription', 50)->nullable(); // 10x, 12, 30", 15" cada
            $table->string('notes')->nullable();            // deitada, PESADO…
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_exercises');
        Schema::dropIfExists('sheet_blocks');
        Schema::dropIfExists('workout_sheets');
        Schema::dropIfExists('exercise_images');
        Schema::dropIfExists('exercises');
        Schema::dropIfExists('categories');
    }
};
