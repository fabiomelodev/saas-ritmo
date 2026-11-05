<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/** Botão "Enviar teste" na tela de notificações: só push, não vai para o sino */
class TestPush extends Notification
{
    public function via(User $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(User $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Notificações ativadas')
            ->body('É assim que os avisos do '.config('app.name').' vão chegar neste aparelho.')
            ->icon('/icons/icon-192.png')
            ->badge('/icons/badge-96.png')
            ->tag('test-push')
            ->lang('pt-BR')
            ->data(['url' => route('notifications')])
            ->options(['TTL' => 600]);
    }
}
