<?php

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterStudentFromInvite;
use App\Actions\RegisterTrainer;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * Login/cadastro com Google.
 * intent=login   → só entra se a conta já existir
 * intent=trainer → cria conta de treinador se não existir
 * intent=invite  → cria conta de aluno vinculada ao convite (code)
 */
class GoogleController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $intent = in_array($request->query('intent'), ['login', 'trainer', 'invite'], true)
            ? $request->query('intent')
            : 'login';

        session([
            'oauth.intent' => $intent,
            'oauth.code' => $request->query('code'),
        ]);

        return Socialite::driver('google')->redirect();
    }

    public function callback(RegisterTrainer $registerTrainer, RegisterStudentFromInvite $registerStudent): RedirectResponse
    {
        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('login')->with('status', 'Não foi possível entrar com o Google. Tente de novo.');
        }

        $intent = session()->pull('oauth.intent', 'login');
        $code = session()->pull('oauth.code');

        // Conta existente: por google_id ou pelo e-mail (vincula o Google a ela)
        $user = User::where('google_id', $google->getId())->first()
            ?? User::where('email', $google->getEmail())->first();

        if ($user) {
            if (! $user->google_id) {
                $user->forceFill(['google_id' => $google->getId(), 'email_verified_at' => $user->email_verified_at ?? now()])->save();
            }
        } elseif ($intent === 'trainer') {
            $user = $registerTrainer->handle([
                'name' => $google->getName() ?: $google->getEmail(),
                'email' => $google->getEmail(),
                'google_id' => $google->getId(),
            ]);
        } elseif ($intent === 'invite' && $code && ($invite = RegisterStudentFromInvite::findOpenInvite($code))) {
            $user = $registerStudent->handle($invite, [
                'name' => $google->getName() ?: $google->getEmail(),
                'email' => $google->getEmail(),
                'google_id' => $google->getId(),
            ]);
        } else {
            return redirect()->route('login')->with('status',
                'Não encontramos uma conta com esse Google. Treinador: crie sua conta. Aluno: use o link enviado pelo seu treinador.');
        }

        Auth::login($user, remember: true);
        session()->regenerate();

        return redirect()->route('home');
    }
}
