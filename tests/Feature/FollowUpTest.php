<?php

namespace Tests\Feature;

use App\Actions\CreatePlan;
use App\Livewire\Student\Progress;
use App\Livewire\Student\Session as StudentSession;
use App\Livewire\Trainer\Logs;
use App\Livewire\Trainer\StudentProfile;
use App\Models\Plan;
use App\Models\PlanSession;
use App\Models\SessionLog;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FollowUpTest extends TestCase
{
    use RefreshDatabase;

    private User $trainer;

    private User $student;

    private Plan $plan;

    private SessionLog $log;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trainer = User::factory()->trainer()->create();
        $this->student = User::factory()->create(['name' => 'Camile Teste']);
        $this->trainer->students()->attach($this->student->id, ['status' => 'active']);

        $this->plan = app(CreatePlan::class)->handle(
            $this->trainer, $this->student, 'Plano de teste',
            now()->startOfWeek(\Carbon\CarbonInterface::MONDAY)->subWeek(), null, [1 => 15, 2 => 15, 3 => 17, 4 => 18],
        );
        $this->plan->publish();

        $run = $this->sessionOn(now()->subDay());
        $run->update(['type' => 'run', 'title' => 'Regenerativo', 'distance_km' => 5, 'pace' => '7', 'pace_unit' => 'kmh']);

        $this->log = $run->log()->create([
            'student_id' => $this->student->id,
            'status' => 'done',
            'distance_km' => 5,
            'duration_seconds' => 2580,
            'rpe' => 4,
            'comment' => 'Senti a panturrilha',
            'completed_at' => now()->subDay(),
        ]);
    }

    private function sessionOn(CarbonInterface $date): PlanSession
    {
        $plan = $this->plan->fresh('weeks.sessions');

        return $plan->weeks->firstWhere('number', $plan->weekNumberFor($date))->sessions->firstWhere('day_of_week', $date->dayOfWeekIso);
    }

    public function test_trainer_sees_new_log_with_badge_and_details(): void
    {
        $this->assertSame(1, $this->trainer->unseenLogsCount());

        $this->actingAs($this->trainer)
            ->get(route('trainer.logs'))
            ->assertOk()
            ->assertSee('Senti a panturrilha')
            ->assertSee('8:36 min/km')
            ->assertSee('Novo');
    }

    public function test_trainer_replies_and_log_leaves_pending_filter(): void
    {
        $this->actingAs($this->trainer);

        $component = Livewire::test(Logs::class)
            ->assertSet('filter', 'pending')
            ->call('reply', $this->log->id, 'Mandou bem!');

        $this->assertSame('Mandou bem!', $this->log->replies()->first()->body);
        $this->assertNotNull($this->log->fresh()->seen_by_trainer_at);
        $this->assertCount(0, $component->get('logs'));
        $this->assertSame(0, $component->get('counts')['pending']);

        $component->set('drafts.'.$this->log->id, 'Alongue bem a panturrilha')
            ->set('filter', 'all')
            ->call('reply', $this->log->id)
            ->assertHasNoErrors();

        $this->assertSame(2, $this->log->replies()->count());
    }

    public function test_hard_effort_and_skipped_filters(): void
    {
        $this->log->update(['rpe' => 9]);
        $this->actingAs($this->trainer);

        $counts = Livewire::test(Logs::class)->get('counts');

        $this->assertSame(1, $counts['hard']);
        $this->assertSame(0, $counts['skipped']);
    }

    public function test_trainer_cannot_reply_to_another_trainers_student(): void
    {
        $other = User::factory()->trainer()->create();

        $this->actingAs($other);

        Livewire::test(Logs::class)
            ->call('reply', $this->log->id, 'Oi')
            ->assertNotFound();

        $this->get(route('trainer.students.show', $this->student))->assertNotFound();
    }

    public function test_student_sees_reply_and_answers_back(): void
    {
        $this->log->replies()->create(['user_id' => $this->trainer->id, 'body' => 'Alongue bem!']);
        $this->log->update(['seen_by_trainer_at' => now()]);

        $this->actingAs($this->student)
            ->get(route('student.home'))
            ->assertSee('Alongue bem!');

        Livewire::test(StudentSession::class, ['session' => $this->log->session])
            ->assertSee('Alongue bem!')
            ->set('chat', 'Pode deixar!')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $this->assertSame('Pode deixar!', $this->log->replies()->latest('id')->first()->body);
        $this->assertNull($this->log->fresh()->seen_by_trainer_at, 'volta como novo para o treinador');
    }

    public function test_trainer_profile_saves_goal_level_and_notes(): void
    {
        $this->actingAs($this->trainer);

        $this->get(route('trainer.students.show', $this->student))
            ->assertOk()
            ->assertSee('Camile Teste')
            ->assertSee('Senti a panturrilha');

        Livewire::test(StudentProfile::class, ['student' => $this->student])
            ->set('goal', 'Fortalecer e ganhar condicionamento')
            ->set('level', 'Iniciante')
            ->set('notes', 'Cuidado com a panturrilha')
            ->call('save')
            ->assertHasNoErrors();

        $pivot = $this->trainer->students()->first()->pivot;
        $this->assertSame('Iniciante', $pivot->level);
        $this->assertSame('Cuidado com a panturrilha', $pivot->private_notes);
    }

    public function test_archiving_removes_plan_access_and_restore_brings_it_back(): void
    {
        $this->actingAs($this->trainer);
        Livewire::test(StudentProfile::class, ['student' => $this->student])->call('archive');

        $this->assertCount(0, $this->trainer->activeStudents);

        $this->actingAs($this->student)
            ->get(route('student.home'))
            ->assertSee('Seu plano está sendo montado');
        $this->get(route('student.session', $this->log->session))->assertNotFound();

        $this->actingAs($this->trainer);
        Livewire::test(StudentProfile::class, ['student' => $this->student])->call('restore');

        $this->actingAs($this->student)
            ->get(route('student.home'))
            ->assertDontSee('Seu plano está sendo montado');
    }

    public function test_progress_page_shows_weekly_volume_and_history(): void
    {
        $this->actingAs($this->student);

        $summary = Livewire::test(Progress::class)->get('summary');

        $this->assertSame(5.0, $summary['km']);
        $this->assertSame(65.0, $summary['goal']);
        $this->assertSame(1, $summary['done']);

        $this->get(route('student.progress'))
            ->assertOk()
            ->assertSee('Volume semanal')
            ->assertSee('Regenerativo');
    }

    public function test_student_adds_and_deletes_personal_record(): void
    {
        $this->actingAs($this->student);

        Livewire::test(Progress::class)
            ->set('adding', true)
            ->set('label', '10 km')
            ->set('time', '1:15:30')
            ->set('achievedOn', '2026-07-09')
            ->call('addRecord')
            ->assertHasNoErrors();

        $record = $this->student->personalRecords()->first();
        $this->assertSame('10 km', $record->label);
        $this->assertSame(4530, $record->time_seconds);
        $this->assertSame('7:33 min/km', $record->paceLabel());

        Livewire::test(Progress::class)
            ->set('label', 'other')
            ->set('customLabel', '15 km')
            ->set('customDistance', '15')
            ->set('time', 'abc')
            ->call('addRecord')
            ->assertHasErrors('time');

        Livewire::test(Progress::class)->call('deleteRecord', $record->id);
        $this->assertSame(0, $this->student->personalRecords()->count());
    }
}
