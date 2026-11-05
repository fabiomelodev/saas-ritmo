<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class NotificationController extends Controller
{
    /** Abre o aviso: marca como lido e vai para a tela relacionada */
    public function open(string $id): RedirectResponse
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? route('home');

        // Só segue links internos do app
        return redirect()->to(str_starts_with($url, url('/')) ? $url : route('home'));
    }
}
