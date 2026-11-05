<?php

namespace Tests\Feature;

use App\Actions\CreatePlan;
use App\Livewire\Student\Exercise as StudentExercise;
use App\Livewire\Student\Home;
use App\Livewire\Student\Session as StudentSession;
use App\Models\Exercise;
use App\Models\Plan;
use App\Models\PlanSession;
use App\Models\User;
use App\Models\WorkoutSheet;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudentTrainingTest extends TestCase
{
    use RefreshDatabase;

    private User $trainer;

    private User $student;

    private Plan $plan;

    private WorkoutSheet $sheet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trainer = User::factory()->trainer()->create();
        $this->student = User::factory()->create();
        $this->trainer->students()->attach($this->student->id, ['status' => 'active']);

        // Ficha com um par de exercícios (como na planilha)
        $this->sheet = WorkoutSheet::create(['trainer_id' => $this->trainer->id, 'name' => 'Treino A']);
        $block = $this->sheet->blocks()->create(['section' => 'strength', 'sets' => 3, 'position' => 0]);
        $squat = Exercise::create(['trainer_id' => $this->trainer->id, 'name' => 'Agachamento hack', 'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ', 'video_start_seconds' => 42]);
        $jump = Exercise::create(['trainer_id' => $this->trainer->id, 'name' => 'Drop jump']);
        $block->items()->create(['exercise_id' => $squat->id, 'prescription' => '10x', 'position' => 0]);
        $block->items()->create(['exercise_id' => $jump->id, 'prescription' => '5x', 'position' => 1]);

        // Plano começando na segunda da semana passada: ontem e hoje sempre estão dentro dele
        $this->plan = app(CreatePlan::class)->handle(
            $this->trainer, $this->student, 'Plano de teste',
            now()->startOfWeek(\Carbon\CarbonInterface::MONDAY)->subWeek(), null, [1 => 15, 2 => 15, 3 => 17, 4 => 18],
        );
        $this->plan->publish();

        $this->sessionOn(now()->subDay())->update(['type' => 'run', 'title' => 'Regenerativo', 'distance_km' => 5, 'pace' => '7', 'pace_unit' => 'kmh', 'notes' => 'Trote leve']);
        $this->sessionOn(now())->update(['type' => 'strength', 'title' => 'Força A e mobilidade', 'workout_sheet_id' => $this->sheet->id]);
        $this->sessionOn(now()->addDay())->update(['type' => 'run', 'title' => 'Corrida Z2', 'distance_km' => 6]);

        $this->actingAs($this->student);
    }

    private function sessionOn(CarbonInterface $date): PlanSession
    {
        $plan = $this->plan->fresh('weeks.sessions');
        $week = $plan->weekNumberFor($date);

        return $plan->weeks->firstWhere('number', $week)->sessions->firstWhere('day_of_week', $date->dayOfWeekIso);
    }

    public function test_home_shows_today_and_pending_registration(): void
    {
        $this->get(route('student.home'))
            ->assertOk()
            ->assertSee('Força A e mobilidade')
            ->assertSee('Começar treino')
            ->assertSee('Ontem: Regenerativo');
    }

    public function test_month_plan_lists_the_week(): void
    {
        $this->get(route('student.plan'))
            ->assertOk()
            ->assertSee('Plano de teste')
            ->assertSee('Força A e mobilidade')
            ->assertSee('Hoje');
    }

    public function test_student_logs_a_run_with_pace_and_effort(): void
    {
        $run = $this->sessionOn(now()->subDay());

        $component = Livewire::test(StudentSession::class, ['session' => $run])
            ->assertSet('distance', '5')
            ->set('duration', '43:00');

        $this->assertSame(['pace' => '8:36', 'speed' => '7,0'], $component->get('pace'));

        $component->call('complete')->assertHasErrors('rpe');

        $component->set('rpe', 4)
            ->set('comment', 'Consegui conversar o tempo todo')
            ->call('complete')
            ->assertHasNoErrors()
            ->assertRedirect(route('student.home'));

        $log = $run->fresh()->log;
        $this->assertSame('done', $log->status);
        $this->assertSame(5.0, $log->distance_km);
        $this->assertSame(2580, $log->duration_seconds);
        $this->assertSame(4, $log->rpe);
        $this->assertSame('8:36', $log->paceMinPerKm());
    }

    public function test_student_can_say_they_did_not_train(): void
    {
        $run = $this->sessionOn(now()->subDay());

        Livewire::test(StudentSession::class, ['session' => $run])
            ->set('comment', 'Chovendo muito')
            ->call('skip');

        $this->assertSame('skipped', $run->fresh()->log->status);
        $this->assertSame('skipped', $run->fresh()->status());
    }

    public function test_strength_checklist_saves_progress_and_completes(): void
    {
        $today = $this->sessionOn(now());
        $items = $this->sheet->blocks->first()->items()->pluck('id')->all();

        $component = Livewire::test(StudentSession::class, ['session' => $today])
            ->assertSee('Agachamento hack')
            ->call('toggle', $items[0]);

        $this->assertSame('in_progress', $today->fresh()->log->status);
        $this->assertSame([$items[0]], $today->fresh()->log->checked_items);

        $component->call('toggle', $items[1])->call('complete')->assertHasNoErrors();

        $this->assertSame('done', $today->fresh()->log->status);
        $this->assertEqualsCanonicalizing($items, $today->fresh()->log->checked_items);
    }

    public function test_future_session_cannot_be_logged_yet(): void
    {
        $tomorrow = $this->sessionOn(now()->addDay());

        Livewire::test(StudentSession::class, ['session' => $tomorrow])
            ->assertSee('O registro libera no dia')
            ->set('duration', '40:00')
            ->set('rpe', 3)
            ->call('complete')
            ->assertForbidden();
    }

    public function test_exercise_page_shows_video_and_marks_done(): void
    {
        $today = $this->sessionOn(now());
        $item = $this->sheet->blocks->first()->items()->orderBy('position')->first();

        $this->get(route('student.exercise', [$today, $item]))
            ->assertOk()
            ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ?start=42', false)
            ->assertSee('JUNTO COM DROP JUMP');

        Livewire::test(StudentExercise::class, ['session' => $today, 'item' => $item])
            ->call('markDone')
            ->assertRedirect(route('student.session', $today));

        $this->assertSame([$item->id], $today->fresh()->log->checked_items);
    }

    public function test_exercise_from_another_sheet_is_not_found(): void
    {
        $today = $this->sessionOn(now());
        $other = WorkoutSheet::create(['trainer_id' => $this->trainer->id, 'name' => 'Treino B']);
        $foreignItem = $other->blocks()->create(['section' => 'strength', 'sets' => 3])
            ->items()->create(['exercise_id' => Exercise::first()->id]);

        $this->get(route('student.exercise', [$today, $foreignItem]))->assertNotFound();
    }

    public function test_student_cannot_open_someone_elses_or_draft_sessions(): void
    {
        $today = $this->sessionOn(now());

        $this->actingAs(User::factory()->create())
            ->get(route('student.session', $today))
            ->assertNotFound();

        $this->plan->update(['status' => 'draft']);

        $this->actingAs($this->student)
            ->get(route('student.session', $today))
            ->assertNotFound();
    }

    public function test_rest_day_redirects_to_plan(): void
    {
        $rest = $this->plan->fresh('weeks.sessions')->weeks->last()->sessions->firstWhere('type', \App\Enums\SessionType::Rest);

        $this->get(route('student.session', $rest))->assertRedirect(route('student.plan'));
    }

    public function test_week_progress_counts_done_sessions(): void
    {
        $today = $this->sessionOn(now());
        $today->log()->create(['student_id' => $this->student->id, 'status' => 'done', 'completed_at' => now()]);

        $progress = Livewire::test(Home::class)->get('progress');

        $this->assertSame(1, $progress['done']);
    }
}
