<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Auth\InviteRegister;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\RegisterTrainer;
use App\Livewire\Onboarding\CompleteProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_trainer_registers_and_gets_an_invite_code(): void
    {
        Livewire::test(RegisterTrainer::class)
            ->set('name', 'Rafael Treinador')
            ->set('email', 'rafael@example.com')
            ->set('password', 'senha-segura')
            ->call('register')
            ->assertRedirect(route('onboarding'));

        $trainer = User::where('email', 'rafael@example.com')->first();

        $this->assertSame(Role::Trainer, $trainer->role);
        $this->assertNotNull($trainer->trainerProfile->invite_code);
        $this->assertAuthenticatedAs($trainer);
    }

    public function test_student_registers_through_invite_and_is_linked_to_trainer(): void
    {
        $trainer = User::factory()->trainer()->create();
        $code = $trainer->trainerProfile->invite_code;

        $this->get(route('invite.show', $code))->assertOk()->assertSee($trainer->name);

        Livewire::test(InviteRegister::class, ['code' => $code])
            ->set('name', 'Camile Teste')
            ->set('email', 'camile@example.com')
            ->set('password', 'senha-segura')
            ->call('register')
            ->assertRedirect(route('onboarding'));

        $student = User::where('email', 'camile@example.com')->first();

        $this->assertSame(Role::Student, $student->role);
        $this->assertTrue($trainer->activeStudents()->whereKey($student->id)->exists());
    }

    public function test_paused_invite_shows_unavailable_message(): void
    {
        $trainer = User::factory()->trainer()->create();
        $trainer->trainerProfile->update(['accepting_invites' => false]);

        $this->get(route('invite.show', $trainer->trainerProfile->invite_code))
            ->assertOk()
            ->assertSee('Convite indisponível');

        $this->get(route('invite.show', 'nao-existe'))->assertSee('Convite indisponível');
    }

    public function test_student_completes_profile_with_birth_date_and_sex(): void
    {
        $student = User::factory()->incompleteProfile()->create();

        $this->actingAs($student);

        Livewire::test(CompleteProfile::class)
            ->set('whatsapp', '(11) 98888-7777')
            ->set('birth_date', '1995-05-20')
            ->set('sex', 'female')
            ->call('save')
            ->assertHasErrors(['terms' => 'accepted'])
            ->set('terms', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('home'));

        $student->refresh();
        $this->assertTrue($student->hasCompletedProfile());
        $this->assertSame('1995-05-20', $student->birth_date->toDateString());
    }

    public function test_incomplete_profile_is_redirected_to_onboarding(): void
    {
        $student = User::factory()->incompleteProfile()->create();

        $this->actingAs($student)->get(route('student.home'))->assertRedirect(route('onboarding'));
    }

    public function test_roles_cannot_open_each_others_pages(): void
    {
        $trainer = User::factory()->trainer()->create();
        $student = User::factory()->create();

        $this->actingAs($student)->get(route('trainer.students'))->assertRedirect(route('home'));
        $this->actingAs($trainer)->get(route('student.home'))->assertRedirect(route('home'));
        $this->actingAs($trainer)->get(route('trainer.students'))->assertOk();
    }

    public function test_login_with_wrong_password_fails(): void
    {
        $user = User::factory()->create();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'errada')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_demo_seed_builds_november_plan_from_spreadsheet(): void
    {
        $this->seed();

        $camile = User::where('email', 'camile@ritmo.test')->first();
        $plan = $camile->plans()->with('weeks.sessions')->first();

        $this->assertCount(4, $plan->weeks);
        $this->assertSame(28, $plan->weeks->sum(fn ($w) => $w->sessions->count()));

        // Mesmos totais da aba "Resumo & Recordes"
        $this->assertSame([15.0, 15.0, 17.0, 18.0], $plan->weeks->map->plannedKm()->all());
    }
}
