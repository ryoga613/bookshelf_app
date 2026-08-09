<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    public function test_can_see_notifications(): void
    {
        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $notification = Notification::create([
            'type' => 'review_liked',
            'user_id' => $user->id,
            'data' => json_encode([
                'message' => 'あなたのレビューにいいねがつきました',
            ]),
            'read_at' => null, ]);

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertViewIs('Notifications.index');
        $response->assertViewHas('notifications');
    }

    public function test_can_update_read_at()
    {
        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $notification = Notification::create([
            'type' => 'review_liked',
            'user_id' => $user->id,
            'data' => json_encode([
                'message' => 'あなたのレビューにいいねがつきました',
            ]),
            'read_at' => null, ]);
        $response = $this->actingAs($user)->post(route('notifications.read', $notification));
        $this->assertNotNull($notification->fresh()->read_at);
    }
}
