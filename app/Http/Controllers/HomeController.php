<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class HomeController extends Controller
{
    /** Manda cada papel para a sua tela inicial */
    public function __invoke(): RedirectResponse
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->hasCompletedProfile()) {
            return redirect()->route('onboarding');
        }

        return $user->isTrainer()
            ? redirect()->route('trainer.students')
            : redirect()->route('student.home');
    }
}
