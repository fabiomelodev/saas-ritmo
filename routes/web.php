<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DataExportController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\NotificationController;
use App\Livewire\Account\EditProfile as AccountEdit;
use App\Livewire\Account\Notifications as AccountNotifications;
use App\Livewire\Account\Privacy as AccountPrivacy;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Student\Profile as StudentProfile;
use App\Livewire\Trainer\Account as TrainerAccount;
use App\Livewire\Auth\InviteRegister;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\RegisterTrainer;
use App\Livewire\Onboarding\CompleteProfile;
use App\Livewire\Student\Exercise as StudentExercise;
use App\Livewire\Student\Home as StudentHome;
use App\Livewire\Student\Plan as StudentPlan;
use App\Livewire\Student\Progress as StudentProgress;
use App\Livewire\Student\Session as StudentSession;
use App\Livewire\Trainer\Categories as TrainerCategories;
use App\Livewire\Trainer\Exercises\Edit as ExercisesEdit;
use App\Livewire\Trainer\Logs as TrainerLogs;
use App\Livewire\Trainer\Plans\Create as PlansCreate;
use App\Livewire\Trainer\Plans\Edit as PlansEdit;
use App\Livewire\Trainer\Plans\Index as PlansIndex;
use App\Livewire\Trainer\Sheets\Edit as SheetsEdit;
use App\Livewire\Trainer\Sheets\Index as SheetsIndex;
use App\Livewire\Trainer\StudentProfile as TrainerStudentProfile;
use App\Livewire\Trainer\Students as TrainerStudents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Textos legais (públicos)
Route::get('/termos', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/privacidade', [LegalController::class, 'privacy'])->name('legal.privacy');

// Visitantes
Route::middleware('guest')->group(function () {
    Route::get('/entrar', Login::class)->name('login');
    Route::get('/treinador/cadastro', RegisterTrainer::class)->name('trainer.register');
    Route::get('/convite/{code}', InviteRegister::class)->name('invite.show');

    Route::get('/esqueci-senha', ForgotPassword::class)->name('password.request');
    Route::get('/redefinir-senha/{token}', ResetPassword::class)->name('password.reset');

    Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});

// Logados
Route::middleware('auth')->group(function () {
    Route::get('/completar-perfil', CompleteProfile::class)->name('onboarding');

    Route::post('/sair', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

    Route::middleware('profile.completed')->group(function () {
        // Conta (aluno e treinador)
        Route::get('/conta/editar', AccountEdit::class)->name('account.edit');
        Route::get('/conta/privacidade', AccountPrivacy::class)->name('account.privacy');
        Route::get('/conta/exportar', DataExportController::class)->name('account.export');
        Route::get('/notificacoes', AccountNotifications::class)->name('notifications');
        Route::get('/notificacoes/{id}', [NotificationController::class, 'open'])->name('notifications.open');

        // Treinador
        Route::middleware('role:trainer')->prefix('treinador')->name('trainer.')->group(function () {
            Route::get('/alunos', TrainerStudents::class)->name('students');
            Route::get('/alunos/{student}', TrainerStudentProfile::class)->name('students.show');
            Route::get('/registros', TrainerLogs::class)->name('logs');

            Route::get('/planos', PlansIndex::class)->name('plans.index');
            Route::get('/planos/novo', PlansCreate::class)->name('plans.create');
            Route::get('/planos/{plan}', PlansEdit::class)->name('plans.edit');

            Route::get('/fichas', SheetsIndex::class)->name('sheets.index');
            Route::get('/fichas/{sheet}', SheetsEdit::class)->name('sheets.edit');

            Route::get('/exercicios/{exercise}', ExercisesEdit::class)->name('exercises.edit');
            Route::get('/categorias', TrainerCategories::class)->name('categories');
            Route::get('/conta', TrainerAccount::class)->name('account');
        });

        // Aluno
        Route::middleware('role:student')->group(function () {
            Route::get('/hoje', StudentHome::class)->name('student.home');
            Route::get('/plano', StudentPlan::class)->name('student.plan');
            Route::get('/evolucao', StudentProgress::class)->name('student.progress');
            Route::get('/perfil', StudentProfile::class)->name('student.profile');
            Route::get('/treino/{session}', StudentSession::class)->name('student.session');
            Route::get('/treino/{session}/exercicio/{item}', StudentExercise::class)->name('student.exercise');
        });
    });
});
