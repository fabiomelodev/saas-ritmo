<?php

namespace Tests\Feature;

use App\Actions\CreatePlan;
use App\Livewire\Account\EditProfile;
use App\Livewire\Account\Notifications;
use App\Livewire\Account\Privacy;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\InviteRegister;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Student\Session as StudentSession;
use App\Livewire\Trainer\Account;
use App\Livewire\Trainer\Logs;
use App\Livewire\Trainer\Plans\Edit as PlanEdit;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\PlanPublished;
use App\Notifications\StudentJoined;
use App\Notifications\TrainerReplied;
use App\Notifications\TrainingLogged;
use App\Notifications\TrainingReminder;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AccountTest extends TestCase
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
    }

    /** Plano publicado começando na segunda passada, com corrida ontem e hoje */
    private function planWithRuns(): Plan
    {
        $plan = app(CreatePlan::class)->handle($this->trainer, $this->student, 'Plano de teste', now()->startOfWeek(\Carbon\CarbonInterface::MONDAY)->subWeek(), null, [1 => 15, 2 => 15]);
        $plan->publish();
        $plan->load('weeks.sessions');

        foreach ([now()->subDay(), now()] as $date) {
            $plan->weeks->firstWhere('number', $plan->weekNumberFor($date))->sessions
                ->firstWhere('day_of_week', $date->dayOfWeekIso)
                ->update(['type' => 'run', 'title' => 'Corrida Z2', 'distance_km' => 6]);
        }

        return $plan->fresh('weeks.sessions');
    }

    private function sessionOn(Plan $plan, $date)
    {
        return $plan->weeks->firstWhere('number', $plan->weekNumberFor($date))->sessions->firstWhere('day_of_week', $date->dayOfWeekIso);
    }

    /* ---------- Senha ---------- */

    public function test_forgot_and_reset_password(): void
    {
        Notification::fake();

        Livewire::test(ForgotPassword::class)
            ->set('email', $this->student->email)
            ->call('send')
            ->assertSet('sent', true);

        Notification::assertSentTo($this->student, ResetPasswordNotification::class, function ($notification) {
            Livewire::test(ResetPassword::class, ['token' => $notification->token])
                ->set('email', $this->student->email)
                ->set('password', 'nova-senha-123')
                ->set('password_confirmation', 'nova-senha-123')
                ->call('save')
                ->assertHasNoErrors()
                ->assertRedirect(route('home'));

            return true;
        });

        $this->assertTrue(Hash::check('nova-senha-123', $this->student->fresh()->password));
        $this->get(route('password.request'))->assertRedirect(); // logado: guest manda para home
    }

    public function test_forgot_password_does_not_reveal_unknown_email(): void
    {
        Livewire::test(ForgotPassword::class)
            ->set('email', 'ninguem@example.com')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('sent', true);
    }

    /* ---------- Conta ---------- */

    public function test_student_edits_data_and_changes_password(): void
    {
        $this->actingAs($this->student);

        $this->get(route('student.profile'))->assertOk()->assertSee('MEUS DADOS');

        Livewire::test(EditProfile::class)
            ->set('name', 'Camile Vitória')
            ->set('whatsapp', '(11) 97777-6666')
            ->set('birth_date', '1994-03-10')
            ->set('sex', 'female')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('student.profile'));

        $this->assertSame('Camile Vitória', $this->student->fresh()->name);

        Livewire::test(EditProfile::class)
            ->set('current_password', 'errada')
            ->set('password', 'nova-senha-123')
            ->set('password_confirmation', 'nova-senha-123')
            ->call('updatePassword')
            ->assertHasErrors('current_password')
            ->set('current_password', 'password')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('nova-senha-123', $this->student->fresh()->password));
    }

    public function test_google_only_user_can_create_password(): void
    {
        $this->student->forceFill(['password' => null, 'google_id' => 'g-123'])->save();
        $this->actingAs($this->student);

        Livewire::test(EditProfile::class)
            ->assertSee('Criar senha')
            ->set('password', 'nova-senha-123')
            ->set('password_confirmation', 'nova-senha-123')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue($this->student->fresh()->hasPassword());
    }

    public function test_trainer_edits_profile_and_manages_invite_link(): void
    {
        $this->actingAs($this->trainer);
        $oldCode = $this->trainer->trainerProfile->invite_code;

        Livewire::test(EditProfile::class)
            ->set('cref', '123456-G/SP')
            ->set('city', 'Campinas')
            ->set('state', 'sp')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('SP', $this->trainer->trainerProfile->fresh()->state);

        Livewire::test(Account::class)->call('toggleInvites');

        // O convite é uma página de visitante: confere deslogado
        auth()->logout();
        $this->get(route('invite.show', $oldCode))->assertSee('Convite indisponível');
        $this->actingAs($this->trainer);

        Livewire::test(Account::class)->call('toggleInvites')->call('regenerateInvite');
        $profile = $this->trainer->trainerProfile->fresh();

        $this->assertNotSame($oldCode, $profile->invite_code);
        $this->assertTrue($profile->accepting_invites);
        $this->assertTrue($this->trainer->activeStudents()->whereKey($this->student->id)->exists(), 'aluno continua vinculado');
    }

    /* ---------- Privacidade ---------- */

    public function test_export_my_data_as_json(): void
    {
        $this->student->personalRecords()->create(['label' => '5 km', 'distance_km' => 5, 'time_seconds' => 2070]);

        $response = $this->actingAs($this->student)->get(route('account.export'));

        $response->assertOk()->assertHeader('Content-Type', 'application/json; charset=utf-8');
        $this->assertSame('34:30', $response->json('recordes.0.tempo'));
        $this->assertSame($this->student->email, $response->json('conta.email'));
    }

    public function test_delete_account_requires_confirmation_and_password(): void
    {
        $this->actingAs($this->student);

        Livewire::test(Privacy::class)
            ->set('confirmation', 'excluir')
            ->set('password', 'password')
            ->call('deleteAccount')
            ->assertHasErrors('confirmation');

        Livewire::test(Privacy::class)
            ->set('confirmation', 'EXCLUIR')
            ->set('password', 'password')
            ->call('deleteAccount')
            ->assertRedirect(route('login'));

        $this->assertModelMissing($this->student);
        $this->assertGuest();
    }

    public function test_legal_pages_are_public(): void
    {
        $this->get(route('legal.terms'))->assertOk()->assertSee('Termos de uso');
        $this->get(route('legal.privacy'))->assertOk()->assertSee('Política de privacidade');
    }

    /* ---------- Notificações ---------- */

    public function test_notifications_for_log_reply_publish_and_new_student(): void
    {
        $plan = $this->planWithRuns();
        $yesterday = $this->sessionOn($plan, now()->subDay());

        // Aluno registra → treinador recebe
        $this->actingAs($this->student);
        Livewire::test(StudentSession::class, ['session' => $yesterday])
            ->set('duration', '40:00')->set('rpe', 5)->call('complete');
        $this->assertSame(1, $this->trainer->notifications()->where('type', TrainingLogged::class)->count());

        // Ajustar o mesmo registro não avisa de novo
        Livewire::test(StudentSession::class, ['session' => $yesterday])
            ->set('rpe', 6)->call('complete');
        $this->assertSame(1, $this->trainer->notifications()->where('type', TrainingLogged::class)->count());

        // Treinador responde → aluno recebe
        $this->actingAs($this->trainer);
        Livewire::test(Logs::class)->call('reply', $yesterday->fresh()->log->id, 'Mandou bem!');
        $this->assertSame(1, $this->student->notifications()->where('type', TrainerReplied::class)->count());

        // Publicar plano → aluno recebe
        $draft = app(CreatePlan::class)->handle($this->trainer, $this->student, 'Plano de dezembro', now()->addWeeks(4)->startOfWeek(\Carbon\CarbonInterface::MONDAY), null, []);
        Livewire::test(PlanEdit::class, ['plan' => $draft])->call('publish');
        $this->assertSame(1, $this->student->notifications()->where('type', PlanPublished::class)->count());

        // Novo aluno pelo convite → treinador recebe
        auth()->logout();
        Livewire::test(InviteRegister::class, ['code' => $this->trainer->trainerProfile->invite_code])
            ->set('name', 'Novo Aluno')->set('email', 'novo@example.com')->set('password', 'senha-segura')
            ->call('register');
        $this->assertSame(1, $this->trainer->notifications()->where('type', StudentJoined::class)->count());
    }

    public function test_preferences_turn_notifications_off_and_page_opens_link(): void
    {
        $this->actingAs($this->student);

        Livewire::test(Notifications::class)
            ->assertSet('prefs.trainer_reply', true)
            ->set('prefs.trainer_reply', false);

        $this->assertFalse($this->student->fresh()->wantsNotification('trainer_reply'));

        // Desligado: resposta não gera aviso
        $plan = $this->planWithRuns();
        $log = $this->sessionOn($plan, now()->subDay())->log()->create(['student_id' => $this->student->id, 'status' => 'done', 'completed_at' => now()]);
        $this->actingAs($this->trainer);
        Livewire::test(Logs::class)->call('reply', $log->id, 'Oi');
        $this->assertSame(0, $this->student->notifications()->count());

        // Abrir um aviso marca como lido e redireciona
        $this->trainer->notify(new StudentJoined($this->student));
        $notification = $this->trainer->notifications()->first();

        $this->get(route('notifications.open', $notification->id))
            ->assertRedirect(route('trainer.students.show', $this->student));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_daily_reminders_command(): void
    {
        $plan = $this->planWithRuns();

        $this->artisan('ritmo:lembretes', ['tipo' => 'hoje'])->assertSuccessful();
        $this->artisan('ritmo:lembretes', ['tipo' => 'hoje'])->assertSuccessful(); // não duplica

        $this->assertSame(1, $this->student->notifications()->where('type', TrainingReminder::class)->count());

        // Registrou: o lembrete das 20h não vai
        $this->sessionOn($plan, now())->log()->create(['student_id' => $this->student->id, 'status' => 'done', 'completed_at' => now()]);
        $this->artisan('ritmo:lembretes', ['tipo' => 'pendentes'])->assertSuccessful();

        $this->assertSame(1, $this->student->notifications()->where('type', TrainingReminder::class)->count());
    }
}
