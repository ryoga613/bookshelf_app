<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Notification;
use App\Models\ReadingPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SendGoalReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-15 20:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_creates_reminders_for_each_timing(): void
    {
        $before = ReadingPlan::factory()->create(['target_date' => '2026-11-18']);
        $due = ReadingPlan::factory()->create(['target_date' => '2026-11-15']);
        $after = ReadingPlan::factory()->create(['target_date' => '2026-11-12']);

        $this->artisan('app:send-goal-reminder')
            ->expectsOutput('3件のリマインドを作成しました')
            ->assertSuccessful();

        $this->assertSame('three_days_before', Notification::where('user_id', $before->user_id)->first()->data['timing']);
        $this->assertSame('on_due_date', Notification::where('user_id', $due->user_id)->first()->data['timing']);
        $this->assertSame('three_days_after', Notification::where('user_id', $after->user_id)->first()->data['timing']);
    }

    public function test_skips_completed_plans_and_other_dates(): void
    {
        ReadingPlan::factory()->create([
            'target_date' => '2026-11-18',
            'status' => ReadingPlanStatus::Completed,
        ]);
        ReadingPlan::factory()->create(['target_date' => '2026-11-16']);

        $this->artisan('app:send-goal-reminder')
            ->expectsOutput('0件のリマインドを作成しました')
            ->assertSuccessful();

        $this->assertDatabaseCount('notifications', 0);
    }
}
