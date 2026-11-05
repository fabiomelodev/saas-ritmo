<?php

namespace Tests\Feature;

use App\Enums\PlanStatus;
use App\Livewire\Trainer\Exercises\Edit as ExerciseEdit;
use App\Livewire\Trainer\Plans\Create as PlanCreate;
use App\Livewire\Trainer\Plans\Edit as PlanEdit;
use App\Livewire\Trainer\Sheets\Edit as SheetEdit;
use App\Livewire\Trainer\Sheets\Index as SheetIndex;
use App\Models\Exercise;
use App\Models\Plan;
use App\Models\User;
use App\Models\WorkoutSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TrainerPlanningTest extends TestCase
{
    use RefreshDatabase;

    private User $trainer;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trainer = User::factory()->trainer()->create();
        $this->student = User::factory()->create();
        $this->trainer->students()->attach($this->student->id, ['status' => 'active']);

        $this->actingAs($this->trainer);
    }

    public function test_trainer_pages_open(): void
    {
        $this->get(route('trainer.plans.index'))->assertOk();
        $this->get(route('trainer.plans.create'))->assertOk();
        $this->get(route('trainer.sheets.index'))->assertOk();
    }

    public function test_create_sheet_with_paired_blocks(): void
    {
        Livewire::test(SheetIndex::class)
            ->set('newName', 'Treino A')
            ->call('create');

        $sheet = WorkoutSheet::where('name', 'Treino A')->firstOrFail();

        Livewire::test(SheetEdit::class, ['sheet' => $sheet])
            ->set('blocks.0.sets', 3)
            ->set('blocks.0.items.0.exercise_name', 'Agachamento hack c/ halteres')
            ->set('blocks.0.items.0.prescription', '10x')
            ->call('addItem', 0)
            ->set('blocks.0.items.1.exercise_name', 'Drop jump')
            ->set('blocks.0.items.1.prescription', '5x')
            ->call('addBlock', 'mobility')
            ->set('blocks.1.items.0.exercise_name', 'Gato')
            ->call('save')
            ->assertHasNoErrors();

        $sheet->refresh()->load('blocks.items.exercise');

        $this->assertCount(2, $sheet->blocks);
        $this->assertSame(['Agachamento hack c/ halteres', 'Drop jump'], $sheet->blocks[0]->items->pluck('exercise.name')->all());
        $this->assertSame('mobility', $sheet->blocks[1]->section);
        $this->assertSame(3, Exercise::where('trainer_id', $this->trainer->id)->count());
    }

    public function test_exercise_gets_youtube_link_start_time_and_photo(): void
    {
        Storage::fake('public');

        $exercise = Exercise::create(['trainer_id' => $this->trainer->id, 'name' => 'Drop jump']);

        Livewire::test(ExerciseEdit::class, ['exercise' => $exercise])
            ->set('youtube_url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
            ->set('video_start', '0:42')
            ->set('photos', [UploadedFile::fake()->image('inicio.jpg')])
            ->call('save')
            ->assertHasNoErrors();

        $exercise->refresh();

        $this->assertSame(42, $exercise->video_start_seconds);
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?start=42', $exercise->youtubeEmbedUrl());
        $this->assertCount(1, $exercise->images);
        Storage::disk('public')->assertExists($exercise->images->first()->path);
    }

    public function test_rejects_non_youtube_link(): void
    {
        $exercise = Exercise::create(['trainer_id' => $this->trainer->id, 'name' => 'Gato']);

        Livewire::test(ExerciseEdit::class, ['exercise' => $exercise])
            ->set('youtube_url', 'https://vimeo.com/123')
            ->call('save')
            ->assertHasErrors('youtube_url');
    }

    public function test_new_blank_plan_has_28_rest_days(): void
    {
        Livewire::test(PlanCreate::class)
            ->set('studentId', $this->student->id)
            ->set('startsOn', '2026-12-07')
            ->set('base', 'blank')
            ->set('goals.1', '15')
            ->call('create')
            ->assertHasNoErrors();

        $plan = Plan::where('student_id', $this->student->id)->with('weeks.sessions')->firstOrFail();

        $this->assertSame(PlanStatus::Draft, $plan->status);
        $this->assertSame(28, $plan->weeks->sum(fn ($w) => $w->sessions->count()));
        $this->assertSame(15.0, $plan->weeks->first()->goal_km);
    }

    public function test_create_form_uses_real_calendar_weeks(): void
    {
        Livewire::test(PlanCreate::class)
            ->set('studentId', $this->student->id)
            ->set('startsOn', '2026-11-11') // quarta-feira
            ->assertSet('title', 'Plano 09/11 a 06/12')
            ->assertSee('Ajustado para segunda, 09/11')
            ->assertSee('09–15/nov')
            ->assertSee('30/nov–06/dez')
            ->set('title', 'Ciclo de base')
            ->set('startsOn', '2026-11-16')
            ->assertSet('title', 'Ciclo de base') // título escrito pelo treinador não é trocado
            ->call('create')
            ->assertHasNoErrors();

        $plan = Plan::where('student_id', $this->student->id)->firstOrFail();

        $this->assertSame('2026-11-16', $plan->starts_on->toDateString());
        $this->assertSame('16/11 a 13/12', $plan->periodLabel());
        $this->assertSame('30/nov–06/dez', $plan->weeks()->where('number', 3)->first()->rangeLabel());
    }

    public function test_duplicate_previous_plan_copies_sessions_and_raises_goals(): void
    {
        $this->seedPreviousPlan();

        Livewire::test(PlanCreate::class)
            ->set('studentId', $this->student->id)
            ->assertSet('base', 'duplicate')
            ->set('startsOn', '2026-12-07')
            ->set('increase', true)
            ->set('increasePercent', 10)
            ->call('create')
            ->assertHasNoErrors();

        $copy = Plan::where('student_id', $this->student->id)->latest('id')->with('weeks.sessions')->first();

        $this->assertSame('Longão', $copy->weeks[0]->sessions->firstWhere('day_of_week', 6)->title);
        $this->assertSame(16.5, $copy->weeks[0]->goal_km);
    }

    public function test_edit_session_and_publish(): void
    {
        $plan = $this->seedPreviousPlan();
        $monday = $plan->weeks[0]->sessions->firstWhere('day_of_week', 1);

        Livewire::test(PlanEdit::class, ['plan' => $plan])
            ->call('select', $monday->id)
            ->set('type', 'run')
            ->set('title', 'Corrida em subida')
            ->set('distance_km', '3')
            ->set('pace', '8')
            ->set('notes', 'Esteira com 5° de inclinação')
            ->call('saveSession')
            ->assertHasNoErrors()
            ->call('publish');

        $monday->refresh();
        $this->assertSame(3.0, $monday->distance_km);
        $this->assertSame('kmh', $monday->pace_unit);
        $this->assertSame(PlanStatus::Published, $plan->fresh()->status);
    }

    public function test_strength_session_requires_a_sheet(): void
    {
        $plan = $this->seedPreviousPlan();
        $session = $plan->weeks[0]->sessions->firstWhere('day_of_week', 2);

        Livewire::test(PlanEdit::class, ['plan' => $plan])
            ->call('select', $session->id)
            ->set('type', 'strength')
            ->set('workout_sheet_id', '')
            ->call('saveSession')
            ->assertHasErrors('workout_sheet_id');
    }

    public function test_trainer_cannot_open_another_trainers_plan_or_sheet(): void
    {
        $other = User::factory()->trainer()->create();
        $foreignSheet = WorkoutSheet::create(['trainer_id' => $other->id, 'name' => 'Secreta']);
        $plan = $this->seedPreviousPlan();

        $this->actingAs($other)->get(route('trainer.plans.edit', $plan))->assertForbidden();
        $this->actingAs($this->trainer)->get(route('trainer.sheets.edit', $foreignSheet))->assertForbidden();
    }

    public function test_draft_start_date_can_change_and_follows_default_title(): void
    {
        $plan = app(\App\Actions\CreatePlan::class)->handle(
            $this->trainer, $this->student, Plan::suggestedTitle(\Carbon\Carbon::parse('2026-11-09')),
            \Carbon\Carbon::parse('2026-11-09'), null, [],
        );

        Livewire::test(PlanEdit::class, ['plan' => $plan])
            ->set('startsOn', '2026-11-18') // quarta-feira
            ->call('changeStartDate')
            ->assertHasNoErrors()
            ->assertSet('startsOn', '2026-11-16')
            ->assertSet('planTitle', 'Plano 16/11 a 13/12');

        $plan->refresh();
        $this->assertSame('2026-11-16', $plan->starts_on->toDateString());
        $this->assertSame('2026-11-16', $plan->weeks()->first()->sessions()->first()->date()->toDateString());

        // Título escrito pelo treinador fica como está
        $plan->update(['title' => 'Ciclo de base']);
        Livewire::test(PlanEdit::class, ['plan' => $plan])->set('startsOn', '2026-11-23')->call('changeStartDate');
        $this->assertSame('Ciclo de base', $plan->fresh()->title);
    }

    public function test_published_plan_cannot_change_start_or_be_deleted(): void
    {
        $plan = $this->seedPreviousPlan();
        $plan->publish();

        Livewire::test(PlanEdit::class, ['plan' => $plan])
            ->set('startsOn', '2026-12-07')
            ->call('changeStartDate')
            ->assertForbidden();

        Livewire::test(PlanEdit::class, ['plan' => $plan])->call('deletePlan')->assertForbidden();

        $this->assertModelExists($plan);
    }

    public function test_delete_draft_plan(): void
    {
        $plan = $this->seedPreviousPlan();

        Livewire::test(PlanEdit::class, ['plan' => $plan])
            ->call('deletePlan')
            ->assertRedirect(route('trainer.plans.index'));

        $this->assertModelMissing($plan);
        $this->assertDatabaseCount('plan_sessions', 0);
    }

    public function test_draft_with_student_logs_is_protected(): void
    {
        $plan = $this->seedPreviousPlan();
        $session = $plan->weeks[0]->sessions->firstWhere('day_of_week', 6);
        $session->log()->create(['student_id' => $this->student->id, 'status' => 'done', 'completed_at' => now()]);

        Livewire::test(PlanEdit::class, ['plan' => $plan])
            ->call('deletePlan')
            ->assertNoRedirect()
            ->set('startsOn', '2026-12-07')
            ->call('changeStartDate')
            ->assertHasErrors('startsOn');

        $this->assertModelExists($plan);
    }

    private function seedPreviousPlan(): Plan
    {
        $plan = app(\App\Actions\CreatePlan::class)->handle(
            $this->trainer, $this->student, 'Plano de novembro',
            now()->subWeeks(5)->startOfWeek(\Carbon\CarbonInterface::MONDAY), null, [1 => 15, 2 => 15, 3 => 17, 4 => 18],
        );

        $plan->weeks[0]->sessions->firstWhere('day_of_week', 6)
            ->update(['type' => 'run', 'title' => 'Longão', 'distance_km' => 10]);

        return $plan->fresh(['weeks.sessions']);
    }
}
