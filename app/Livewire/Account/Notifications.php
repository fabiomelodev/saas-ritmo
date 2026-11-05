<?php

namespace App\Livewire\Account;

use App\Notifications\TestPush;
use App\Support\NotificationPreferences;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/** Lista de avisos + preferências (aluno e treinador) */
#[Layout('layouts.app')]
#[Title('Notificações')]
class Notifications extends Component
{
    use WithPagination;

    /** @var array<string, bool> */
    public array $prefs = [];

    public function mount(): void
    {
        $user = auth()->user();

        foreach (NotificationPreferences::for($user) as $key => $label) {
            $this->prefs[$key] = $user->wantsNotification($key);
        }
    }

    #[Computed]
    public function notifications()
    {
        return auth()->user()->notifications()->paginate(20);
    }

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    /** Salva a preferência assim que o usuário liga/desliga */
    public function updatedPrefs($value, string $key): void
    {
        abort_unless(array_key_exists($key, NotificationPreferences::for(auth()->user())), 422);

        $user = auth()->user();
        $user->forceFill([
            'notification_preferences' => array_merge($user->notification_preferences ?? [], [$key => (bool) $value]),
        ])->save();
    }

    /* ---------- Push no celular (inscrição feita no navegador, ver resources/js/push.js) ---------- */

    #[Computed]
    public function pushDevices(): int
    {
        return auth()->user()->pushSubscriptions()->count();
    }

    /** Salva (ou transfere para este usuário) a inscrição deste aparelho */
    public function savePushSubscription(string $endpoint, string $publicKey, string $authToken, string $contentEncoding = 'aes128gcm'): void
    {
        validator(compact('endpoint', 'publicKey', 'authToken', 'contentEncoding'), [
            'endpoint' => ['required', 'url:https', 'max:1000'],
            'publicKey' => ['required', 'string', 'max:255'],
            'authToken' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['required', 'in:aesgcm,aes128gcm'],
        ])->validate();

        auth()->user()->updatePushSubscription($endpoint, $publicKey, $authToken, $contentEncoding);
        unset($this->pushDevices);
    }

    public function removePushSubscription(string $endpoint): void
    {
        auth()->user()->deletePushSubscription($endpoint);
        unset($this->pushDevices);
    }

    public function sendTestPush(): void
    {
        $user = auth()->user();
        abort_unless($user->pushSubscriptions()->exists(), 422);

        $user->notify(new TestPush);
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
        unset($this->notifications, $this->unreadCount);
    }

    public function render()
    {
        return view('livewire.account.notifications', [
            'options' => NotificationPreferences::for(auth()->user()),
            'vapidKey' => config('webpush.vapid.public_key'),
        ]);
    }
}
