<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * 読書計画の所有者認可・読了操作・期限変更時の挙動
 */
class ReadingPlanAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $other;

    private ReadingPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-15 10:00:00');

        $this->owner = User::factory()->create();
        $this->other = User::factory()->create();
        $this->plan = ReadingPlan::factory()->create([
            'user_id' => $this->owner->id,
            'book_id' => Book::factory()->create(['title' => '所有者の本'])->id,
            'target_date' => '2026-11-20',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ---- 一覧 ----

    public function test_index_shows_only_own_plans(): void
    {
        ReadingPlan::factory()->create([
            'user_id' => $this->other->id,
            'book_id' => Book::factory()->create(['title' => '他人の本'])->id,
        ]);

        $this->actingAs($this->owner)->get(route('reading-plans.index'))
            ->assertOk()
            ->assertSee('所有者の本')
            ->assertDontSee('他人の本')
            ->assertViewHas('readingPlans', fn ($plans) => $plans->pluck('id')->all() === [$this->plan->id]);
    }

    // ---- 所有者以外は 403 ----

    public function test_other_user_cannot_see_edit_page(): void
    {
        $this->actingAs($this->other)->get(route('reading-plans.edit', $this->plan))->assertForbidden();
    }

    public function test_other_user_cannot_update(): void
    {
        $this->actingAs($this->other)
            ->put(route('reading-plans.update', $this->plan), ['target_date' => '2026-12-01'])
            ->assertForbidden();

        $this->assertSame('2026-11-20', $this->plan->fresh()->target_date->format('Y-m-d'));
    }

    public function test_other_user_cannot_complete(): void
    {
        $this->actingAs($this->other)->post(route('reading-plans.complete', $this->plan))->assertForbidden();

        $this->assertSame(ReadingPlanStatus::NotCompleted, $this->plan->fresh()->status);
    }

    public function test_other_user_cannot_delete(): void
    {
        $this->actingAs($this->other)->delete(route('reading-plans.destroy', $this->plan))->assertForbidden();

        $this->assertDatabaseHas('reading_plans', ['id' => $this->plan->id]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('reading-plans.index'))->assertRedirect(route('login'));
        $this->get(route('reading-plans.edit', $this->plan))->assertRedirect(route('login'));
        $this->post(route('reading-plans.complete', $this->plan))->assertRedirect(route('login'));
        $this->delete(route('reading-plans.destroy', $this->plan))->assertRedirect(route('login'));

        $this->assertDatabaseHas('reading_plans', ['id' => $this->plan->id]);
    }

    // ---- 所有者の操作 ----

    public function test_owner_can_see_edit_page_with_plan(): void
    {
        $this->actingAs($this->owner)->get(route('reading-plans.edit', $this->plan))
            ->assertOk()
            ->assertViewHas('readingPlan', fn (ReadingPlan $plan) => $plan->is($this->plan))
            ->assertSee('所有者の本');
    }

    public function test_complete_sets_status_and_completed_at(): void
    {
        $this->actingAs($this->owner)
            ->post(route('reading-plans.complete', $this->plan))
            ->assertRedirect(route('reading-plans.index'));

        $plan = $this->plan->fresh();
        $this->assertSame(ReadingPlanStatus::Completed, $plan->status);
        $this->assertSame('2026-11-15 10:00:00', $plan->completed_at->format('Y-m-d H:i:s'));
    }

    // ---- 期限変更 ----

    public function test_changing_target_date_keeps_status(): void
    {
        $this->plan->update(['status' => ReadingPlanStatus::Reading]);

        $this->actingAs($this->owner)
            ->put(route('reading-plans.update', $this->plan), ['target_date' => '2026-12-01'])
            ->assertRedirect(route('reading-plans.index'));

        $plan = $this->plan->fresh();
        $this->assertSame('2026-12-01', $plan->target_date->format('Y-m-d'));
        $this->assertSame(ReadingPlanStatus::Reading, $plan->status);
    }

    public function test_changing_target_date_of_expired_plan_resets_status(): void
    {
        $this->plan->update(['status' => ReadingPlanStatus::Expired, 'target_date' => '2026-11-10']);

        $this->actingAs($this->owner)
            ->put(route('reading-plans.update', $this->plan), ['target_date' => '2026-11-30'])
            ->assertRedirect(route('reading-plans.index'));

        $plan = $this->plan->fresh();
        $this->assertSame('2026-11-30', $plan->target_date->format('Y-m-d'));
        $this->assertSame(ReadingPlanStatus::NotCompleted, $plan->status);
    }

    public function test_target_date_cannot_be_changed_to_past(): void
    {
        $this->actingAs($this->owner)
            ->from(route('reading-plans.edit', $this->plan))
            ->put(route('reading-plans.update', $this->plan), ['target_date' => '2026-11-14'])
            ->assertRedirect(route('reading-plans.edit', $this->plan))
            ->assertSessionHasErrors(['target_date' => '期日は今日以降の日付を指定してください。']);

        $this->assertSame('2026-11-20', $this->plan->fresh()->target_date->format('Y-m-d'));
    }

    public function test_target_date_can_be_changed_to_today(): void
    {
        $this->actingAs($this->owner)
            ->put(route('reading-plans.update', $this->plan), ['target_date' => '2026-11-15'])
            ->assertSessionHasNoErrors();

        $this->assertSame('2026-11-15', $this->plan->fresh()->target_date->format('Y-m-d'));
    }
}
