<?php

namespace Tests\Feature;

use App\Livewire\Account\Notifications;
use App\Livewire\Account\Privacy;
use App\Models\User;
use App\Notifications\StudentJoined;
use App\Notifications\TestPush;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'https://fcm.googleapis.com/fcm/send/abc123';

    private User $trainer;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trainer = User::factory()->trainer()->create();
        $this->student = User::factory()->create();
        $this->trainer->students()->attach($this->student->id, ['status' => 'active']);
    }

    private function subscribe(User $user, string $endpoint = self::ENDPOINT): void
    {
        $user->updatePushSubscription($endpoint, 'chave-p256dh', 'token-auth', 'aes128gcm');
    }

    public function test_device_subscribes_and_unsubscribes(): void
    {
        $this->actingAs($this->student);

        Livewire::test(Notifications::class)
            ->call('savePushSubscription', self::ENDPOINT, 'chave-p256dh', 'token-auth', 'aes128gcm')
            ->assertHasNoErrors()
            ->assertSee('Avisos com o app fechado');

        $this->assertSame(1, $this->student->pushSubscriptions()->count());

        // Mesmo aparelho de novo: não duplica
        Livewire::test(Notifications::class)
            ->call('savePushSubscription', self::ENDPOINT, 'chave-p256dh', 'token-auth', 'aes128gcm');
        $this->assertSame(1, $this->student->pushSubscriptions()->count());

        Livewire::test(Notifications::class)->call('removePushSubscription', self::ENDPOINT);
        $this->assertSame(0, $this->student->pushSubscriptions()->count());
    }

    public function test_insecure_endpoint_is_rejected(): void
    {
        $this->actingAs($this->student);

        Livewire::test(Notifications::class)
            ->call('savePushSubscription', 'http://example.com/push', 'k', 't', 'aes128gcm')
            ->assertHasErrors('endpoint');

        $this->assertSame(0, $this->student->pushSubscriptions()->count());
    }

    public function test_shared_device_moves_to_the_logged_user(): void
    {
        $this->subscribe($this->trainer);
        $this->subscribe($this->student);

        $this->assertSame(0, $this->trainer->pushSubscriptions()->count());
        $this->assertSame(1, $this->student->pushSubscriptions()->count());
    }

    public function test_push_goes_only_to_users_with_a_device(): void
    {
        Notification::fake();

        $this->trainer->notify(new StudentJoined($this->student));
        Notification::assertSentTo($this->trainer, StudentJoined::class, fn ($n, array $channels) => $channels === ['database']);

        $this->subscribe($this->trainer);
        $this->trainer->notify(new StudentJoined($this->student));
        Notification::assertSentTo($this->trainer, StudentJoined::class, fn ($n, array $channels) => in_array(WebPushChannel::class, $channels, true));

        // Preferência desligada: nem sino nem celular
        $this->trainer->forceFill(['notification_preferences' => ['new_student' => false]])->save();
        $this->trainer->notify(new StudentJoined($this->student));
        Notification::assertSentToTimes($this->trainer, StudentJoined::class, 2);
    }

    public function test_push_payload_opens_the_notification(): void
    {
        $notification = new StudentJoined($this->student);
        $notification->id = 'aviso-123';

        $payload = $notification->toWebPush($this->trainer, $notification)->toArray();

        $this->assertStringContainsString($this->student->name, $payload['title'].' '.($payload['body'] ?? ''));
        $this->assertSame(route('notifications.open', 'aviso-123'), $payload['data']['url']);
        $this->assertSame('/icons/icon-192.png', $payload['icon']);
    }

    public function test_test_button_requires_a_device(): void
    {
        Notification::fake();
        $this->actingAs($this->student);

        Livewire::test(Notifications::class)->call('sendTestPush')->assertStatus(422);
        Notification::assertNothingSent();

        $this->subscribe($this->student);
        Livewire::test(Notifications::class)->call('sendTestPush');
        Notification::assertSentTo($this->student, TestPush::class);
    }

    public function test_deleting_account_removes_devices(): void
    {
        $this->subscribe($this->student);
        $this->actingAs($this->student);

        Livewire::test(Privacy::class)
            ->set('confirmation', 'EXCLUIR')
            ->set('password', 'password')
            ->call('deleteAccount');

        $this->assertDatabaseCount('push_subscriptions', 0);
    }
}
