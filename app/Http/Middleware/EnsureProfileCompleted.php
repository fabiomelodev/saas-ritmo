<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Garante que a etapa 2 do cadastro foi concluída */
class EnsureProfileCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->hasCompletedProfile()) {
            return redirect()->route('onboarding');
        }

        return $next($request);
    }
}
