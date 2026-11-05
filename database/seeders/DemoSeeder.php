<?php

namespace Database\Seeders;

use App\Enums\PlanStatus;
use App\Enums\Role;
use App\Enums\Sex;
use App\Models\Category;
use App\Models\Exercise;
use App\Models\Plan;
use App\Models\TrainerProfile;
use App\Models\User;
use App\Models\WorkoutSheet;
use Illuminate\Database\Seeder;

/**
 * Dados de demonstração a partir da planilha "Planilha_de_Treino_de_Corrida_Camile.xlsx".
 *
 * Acessos (senha "password"):
 *  - treinador@ritmo.test
 *  - camile@ritmo.test
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $trainer = User::create([
            'role' => Role::Trainer,
            'name' => 'Treinador Demo',
            'email' => 'treinador@ritmo.test',
            'email_verified_at' => now(),
            'password' => 'password',
            'whatsapp' => '(11) 90000-0000',
            'terms_accepted_at' => now(),
            'profile_completed_at' => now(),
        ]);

        TrainerProfile::create([
            'user_id' => $trainer->id,
            'invite_code' => 'demo1234',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);

        Category::createDefaultsFor($trainer);

        $camile = User::create([
            'role' => Role::Student,
            'name' => 'Camile Vitória Ferreira de Melo',
            'email' => 'camile@ritmo.test',
            'email_verified_at' => now(),
            'password' => 'password',
            'whatsapp' => '(11) 90000-0001',
            'sex' => Sex::Female,
            'terms_accepted_at' => now(),
            'profile_completed_at' => now(),
        ]);

        $trainer->students()->attach($camile->id, [
            'status' => 'active',
            'goal' => 'Fortalecer e ganhar condicionamento',
            'level' => 'Iniciante',
        ]);

        [$sheetA, $sheetB] = $this->seedSheets($trainer);
        $this->seedNovemberPlan($trainer, $camile, $sheetA, $sheetB);
        $this->seedLogs($trainer, $camile);

        // Recordes da aba "Resumo & Recordes"
        $camile->personalRecords()->createMany([
            ['label' => '5 km', 'distance_km' => 5, 'time_seconds' => 34 * 60 + 30, 'achieved_on' => '2026-06-14'],
            ['label' => '10 km', 'distance_km' => 10, 'time_seconds' => 75 * 60 + 30, 'achieved_on' => '2026-07-09'],
        ]);
    }

    /** Aba "Força & Mobilidade": pares de exercícios dividem as séries */
    private function seedSheets(User $trainer): array
    {
        $sheets = [
            'Treino A' => [
                'strength' => [
                    [3, [['Agachamento hack c/ halteres', '10x', 'Membros inferiores'], ['Drop jump', '5x', 'Pliometria']]],
                    [3, [['Remada média neutra c/ elásticos', '12', 'Costas'], ['Abdução de quadril c/ caneleira', '12', 'Quadril', 'Deitada']]],
                    [3, [['Crucifixo c/ halteres', '12', 'Peito'], ['Flexão de joelho c/ bola', '12', 'Posterior']]],
                    [3, [['Anti-rotação c/ elásticos', '12x', 'Core'], ['Abdominal reto c/ pés na bola', '20x', 'Core']]],
                ],
                'mobility' => [
                    ['Gato', '10x'], ['Hip rock', '10x'], ['Alongamento de flexores de quadril', '15" cada'],
                    ['Alongamento de quadríceps', '15" cada'], ['Alcançar pés dinâmico', '10x'], ['Alongamento de iliotibial', '15" cada'],
                ],
            ],
            'Treino B' => [
                'strength' => [
                    [3, [['Agachamento thruster', '10x', 'Membros inferiores'], ['CMJ', '5x', 'Pliometria']]],
                    [3, [['Remada unilateral c/ halter', '12', 'Costas', 'Pesado'], ['Extensão de quadril c/ elástico', '12', 'Quadril']]],
                    [3, [['Supino c/ halteres', '12', 'Peito'], ['Flexão de joelho c/ caneleira', '12', 'Posterior']]],
                    [3, [['Anti-extensão c/ elástico isométrica', '30"', 'Core'], ['Prancha lateral', '30"', 'Core']]],
                ],
                'mobility' => [
                    ['Rotação de ombro c/ elástico', '10x'], ['Alongamento de peitoral no espaldar', '20" cada'],
                    ['Rotação torácica na parede', '10x cada'], ['Dead bug', '15x cada'], ['Prancha', "1'"],
                    ['Anti-flexão c/ elástico', '12x cada'],
                ],
            ],
        ];

        // Grupo muscular da planilha → categoria padrão
        $categories = $trainer->categories()->pluck('id', 'name');
        $categoryFor = fn (string $group) => $categories[match ($group) {
            'Costas', 'Peito' => 'Membros superiores',
            'Quadril', 'Posterior' => 'Membros inferiores',
            default => $group,
        }] ?? null;

        $created = [];

        foreach ($sheets as $name => $content) {
            $sheet = WorkoutSheet::create(['trainer_id' => $trainer->id, 'name' => $name]);
            $position = 0;

            foreach ($content['strength'] as [$sets, $items]) {
                $block = $sheet->blocks()->create(['section' => 'strength', 'sets' => $sets, 'position' => $position++]);

                foreach ($items as $i => $item) {
                    $exercise = Exercise::firstOrCreate(
                        ['trainer_id' => $trainer->id, 'name' => $item[0]],
                        ['category_id' => $categoryFor($item[2])],
                    );
                    $block->items()->create([
                        'exercise_id' => $exercise->id,
                        'prescription' => $item[1],
                        'notes' => $item[3] ?? null,
                        'position' => $i,
                    ]);
                }
            }

            $mobility = $sheet->blocks()->create(['section' => 'mobility', 'sets' => 1, 'position' => $position]);

            foreach ($content['mobility'] as $i => [$exerciseName, $prescription]) {
                $exercise = Exercise::firstOrCreate(
                    ['trainer_id' => $trainer->id, 'name' => $exerciseName],
                    ['category_id' => $categoryFor(str_starts_with($exerciseName, 'Alongamento') ? 'Alongamento' : 'Mobilidade')],
                );
                $mobility->items()->create(['exercise_id' => $exercise->id, 'prescription' => $prescription, 'position' => $i]);
            }

            $created[] = $sheet;
        }

        return $created;
    }

    /** Aba "Plano Mensal": 4 semanas × 7 dias, metas 15/15/17/18 km */
    /** Registros de exemplo nos dois primeiros dias (para a tela de Registros não começar vazia) */
    private function seedLogs(User $trainer, User $student): void
    {
        $week = Plan::where('student_id', $student->id)->first()->weeks()->where('number', 1)->first();
        $monday = $week->sessions()->where('day_of_week', 1)->first();
        $tuesday = $week->sessions()->where('day_of_week', 2)->first();

        $first = $monday->log()->create([
            'student_id' => $student->id,
            'status' => 'done',
            'duration_seconds' => 90 * 60,
            'rpe' => 4,
            'comment' => 'Corri com meu irmão, deu para conversar o tempo todo.',
            'completed_at' => '2026-11-02 19:30:00',
            'seen_by_trainer_at' => '2026-11-02 21:00:00',
        ]);
        $first->replies()->create(['user_id' => $trainer->id, 'body' => 'Mandou bem! É exatamente esse ritmo de Z2.']);

        $tuesday->log()->create([
            'student_id' => $student->id,
            'status' => 'done',
            'distance_km' => 5,
            'duration_seconds' => 43 * 60,
            'rpe' => 4,
            'comment' => 'Senti um pouco a panturrilha no final.',
            'completed_at' => '2026-11-03 07:10:00',
        ]);
    }

    private function seedNovemberPlan(User $trainer, User $student, WorkoutSheet $a, WorkoutSheet $b): void
    {
        $plan = Plan::create([
            'trainer_id' => $trainer->id,
            'student_id' => $student->id,
            'title' => 'Plano de novembro',
            'starts_on' => '2026-11-02', // segunda-feira
            'status' => PlanStatus::Published,
            'published_at' => now(),
        ]);

        $run = fn ($title, $km = null, $pace = null, $unit = 'kmh', $notes = null, $duration = null) => compact('title', 'km', 'pace', 'unit', 'notes', 'duration') + ['type' => 'run'];
        $strength = fn (WorkoutSheet $sheet) => ['type' => 'strength', 'title' => 'Força '.substr($sheet->name, -1).' e mobilidade', 'sheet' => $sheet->id];
        $rest = ['type' => 'rest', 'title' => 'Descanso'];

        // Linhas = semanas; colunas = segunda … domingo
        $grid = [
            1 => [
                $run('Corrida com irmão Z2', null, null, 'kmh', 'PSE controlada com talk test', '90 min'),
                $run('Regenerativo', 5, '7', 'kmh', 'Trote leve'),
                $strength($a),
                $rest,
                $strength($b),
                $run('Corrida Z2', 6, '8:20', 'minkm', 'Manter velocidade leve'),
                $run('Regenerativo', 4, '7', 'kmh', 'Trote leve'),
            ],
            2 => [
                $strength($a),
                $run('HIIT', 4, '9 e 7', 'kmh', "300 m e descansa 2' até completar 4 km", "descanso 2'"),
                $run('Regenerativo', 5, '7', 'kmh', 'Trote leve'),
                $strength($b),
                $run('Corrida Z2', 6, '8:20', 'minkm', 'Manter velocidade leve'),
                $rest,
                $rest,
            ],
            3 => [
                $strength($a),
                $run('Progressivo', 6, null, 'kmh', 'Começa andando e aumenta a velocidade até completar 6 km', "2' cada velocidade"),
                $run('Regenerativo', 5, null, 'kmh', 'Trote leve'),
                $strength($b),
                $run('Corrida Z2', 6, '8:20', 'minkm', 'Manter velocidade leve'),
                $rest,
                $strength($a),
            ],
            4 => [
                $run('Corrida em subida', 3, '8', 'kmh', 'Esteira com 5° de inclinação'),
                $run('Regenerativo', 5, '7', 'kmh', 'Trote leve'),
                $strength($b),
                $rest,
                $strength($a),
                $run('Longão', 10, '8', 'kmh', 'Manter a maior velocidade possível'),
                $rest,
            ],
        ];

        $goals = [1 => 15, 2 => 15, 3 => 17, 4 => 18];

        foreach ($grid as $number => $days) {
            $week = $plan->weeks()->create(['number' => $number, 'goal_km' => $goals[$number]]);

            foreach ($days as $index => $day) {
                $week->sessions()->create([
                    'day_of_week' => $index + 1,
                    'type' => $day['type'],
                    'title' => $day['title'],
                    'distance_km' => $day['km'] ?? null,
                    'pace' => $day['pace'] ?? null,
                    'pace_unit' => isset($day['pace']) ? $day['unit'] : null,
                    'duration' => $day['duration'] ?? null,
                    'notes' => $day['notes'] ?? null,
                    'workout_sheet_id' => $day['sheet'] ?? null,
                ]);
            }
        }
    }
}
