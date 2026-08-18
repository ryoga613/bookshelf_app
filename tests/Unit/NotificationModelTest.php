<?php

namespace Tests\Unit;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationModelTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    use RefreshDatabase;

    public function test_can_get_user(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create([
            'user_id' => $user->id,
        ]);

        $notificationsUser = $notification->user;

        $this->assertTrue($notificationsUser->is($user));
    }
}
