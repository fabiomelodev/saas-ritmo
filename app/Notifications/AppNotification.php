<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Base dos avisos do app, respeitando as preferências do usuário:
 * - banco (sino): gravado na hora (conexão "sync")
 * - push no celular: só para quem ativou em algum aparelho; vai pela fila,
 *   para que o envio (rede externa) não atrase a tela de quem disparou o aviso
 */
abstract class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    abstract public function preference(): string;

    abstract protected function title(): string;

    abstract protected function url(): string;

    protected function body(): ?string
    {
        return null;
    }

    /** chat | calendar | check | users | bolt */
    protected function icon(): string
    {
        return 'bolt';
    }

    /** Dados extras para evitar avisos repetidos (ex.: session_id) */
    protected function extra(): array
    {
        return [];
    }

    /** Por quanto tempo o push ainda vale a pena ser entregue (aparelho desligado, sem sinal…) */
    protected function pushTtl(): int
    {
        return 60 * 60 * 24;
    }

    public function via(User $notifiable): array
    {
        if (! $notifiable->wantsNotification($this->preference())) {
            return [];
        }

        return $notifiable->pushSubscriptions()->exists()
            ? ['database', WebPushChannel::class]
            : ['database'];
    }

    /** @return array<string, string> */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    public function toArray(User $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->body(),
            'url' => $this->url(),
            'icon' => $this->icon(),
            'preference' => $this->preference(),
        ] + $this->extra();
    }

    public function toWebPush(User $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title())
            ->body((string) $this->body())
            ->icon('/icons/icon-192.png')
            ->badge('/icons/badge-96.png')
            ->tag($this->preference().'-'.$this->id)
            ->lang('pt-BR')
            // Abrir o push marca o aviso do sino como lido (mesmo id nos dois canais)
            ->data(['url' => route('notifications.open', $this->id)])
            ->options(['TTL' => $this->pushTtl(), 'urgency' => 'normal']);
    }
}
