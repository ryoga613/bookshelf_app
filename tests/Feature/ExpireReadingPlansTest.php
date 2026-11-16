<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Notification;
use App\Models\ReadingPlan;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * 期限切れバッチ（app:expire-reading-plans）
 */
class ExpireReadingPlansTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-15 00:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function plan(string $targetDate, ReadingPlanStatus $status = ReadingPlanStatus::NotCompleted): ReadingPlan
    {
        return ReadingPlan::factory()->create(['target_date' => $targetDate, 'status' => $status]);
    }

    public function test_overdue_in_progress_plans_are_expired_in_bulk(): void
    {
        $overdueNotStarted = $this->plan('2026-11-14');
        $overdueReading = $this->plan('2026-11-01', ReadingPlanStatus::Reading);
        $overdueLongAgo = $this->plan('2025-01-01');

        $this->artisan('app:expire-reading-plans')
            ->expectsOutput('3件の読書計画を期限切れにしました')
            ->assertSuccessful();

        foreach ([$overdueNotStarted, $overdueReading, $overdueLongAgo] as $plan) {
            $this->assertSame(ReadingPlanStatus::Expired, $plan->fresh()->status);
        }
    }

    public function test_plans_not_yet_overdue_or_finished_are_untouched(): void
    {
        $dueToday = $this->plan('2026-11-15');
        $future = $this->plan('2026-11-20', ReadingPlanStatus::Reading);
        $completed = $this->plan('2026-11-01', ReadingPlanStatus::Completed);
        $alreadyExpired = $this->plan('2026-10-01', ReadingPlanStatus::Expired);

        $this->artisan('app:expire-reading-plans')
            ->expectsOutput('0件の読書計画を期限切れにしました')
            ->assertSuccessful();

        $this->assertSame(ReadingPlanStatus::NotCompleted, $dueToday->fresh()->status);
        $this->assertSame(ReadingPlanStatus::Reading, $future->fresh()->status);
        $this->assertSame(ReadingPlanStatus::Completed, $completed->fresh()->status);
        $this->assertSame(ReadingPlanStatus::Expired, $alreadyExpired->fresh()->status);
    }

    public function test_plan_due_today_is_expired_on_next_day(): void
    {
        $plan = $this->plan('2026-11-15');

        Carbon::setTestNow('2026-11-16 00:00:00');
        $this->artisan('app:expire-reading-plans')->assertSuccessful();

        $this->assertSame(ReadingPlanStatus::Expired, $plan->fresh()->status);
    }

    public function test_expired_plan_still_gets_reminder_three_days_after(): void
    {
        $plan = $this->plan('2026-11-12');

        $this->artisan('app:expire-reading-plans')->assertSuccessful();
        Carbon::setTestNow('2026-11-15 20:00:00');
        $this->artisan('app:send-goal-reminder')->assertSuccessful();

        $this->assertSame(ReadingPlanStatus::Expired, $plan->fresh()->status);
        $this->assertSame('three_days_after', Notification::where('user_id', $plan->user_id)->first()->data['timing']);
    }

    public function test_new_plan_can_be_created_for_book_whose_plan_expired(): void
    {
        $expired = $this->plan('2026-11-01', ReadingPlanStatus::Expired);

        $this->actingAs($expired->user)
            ->post(route('reading-plans.store'), ['book_id' => $expired->book_id, 'target_date' => '2026-12-01'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('reading_plans', 2);
    }

    public function test_batches_are_scheduled_daily(): void
    {
        $events = collect(app(Schedule::class)->events());

        $expire = $events->first(fn ($event) => str_contains($event->command, 'app:expire-reading-plans'));
        $reminder = $events->first(fn ($event) => str_contains($event->command, 'app:send-goal-reminder'));

        $this->assertNotNull($expire);
        $this->assertSame('0 0 * * *', $expire->expression);
        $this->assertNotNull($reminder);
        $this->assertSame('0 20 * * *', $reminder->expression);
    }
}
