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
 * 読書計画の作成・編集画面のバリデーションと、画面に表示されるエラーメッセージを確認する
 */
class ReadingPlanValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Book $book;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-15 12:00:00');
        $this->user = User::factory()->create();
        $this->book = Book::factory()->create(['user_id' => $this->user->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createPlan(array $attributes = []): ReadingPlan
    {
        return ReadingPlan::factory()->create(array_merge([
            'user_id' => $this->user->id,
            'book_id' => $this->book->id,
            'status' => ReadingPlanStatus::NotCompleted,
            'target_date' => '2026-11-20',
        ], $attributes));
    }

    /** 作成画面から送信し、リダイレクト後の画面を返す */
    private function submitCreate(array $input)
    {
        return $this->actingAs($this->user)
            ->from(route('reading-plans.create'))
            ->followingRedirects()
            ->post(route('reading-plans.store'), $input);
    }

    /** 編集画面から送信し、リダイレクト後の画面を返す */
    private function submitEdit(ReadingPlan $plan, array $input)
    {
        return $this->actingAs($this->user)
            ->from(route('reading-plans.edit', $plan))
            ->followingRedirects()
            ->put(route('reading-plans.update', $plan), $input);
    }

    // ---- 正常系 ----

    public function test_store_and_update_succeed_with_valid_input(): void
    {
        $this->actingAs($this->user)
            ->post(route('reading-plans.store'), ['book_id' => $this->book->id, 'target_date' => '2026-11-15'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('reading-plans.index'));

        $plan = ReadingPlan::first();

        // 編集時は自身を重複チェックから除外する
        $this->actingAs($this->user)
            ->put(route('reading-plans.update', $plan), ['target_date' => '2026-12-01'])
            ->assertSessionHasNoErrors();
        $this->assertSame('2026-12-01', $plan->fresh()->target_date->format('Y-m-d'));
    }

    // ---- 1. book_id ----

    // a. 書籍が未選択の場合
    public function test_a_book_not_selected(): void
    {
        $this->submitCreate(['book_id' => '', 'target_date' => '2026-11-20'])
            ->assertOk()->assertSee('書籍を選択してください。');
    }

    public function test_book_id_must_be_integer_and_exist(): void
    {
        $this->actingAs($this->user)
            ->post(route('reading-plans.store'), ['book_id' => 'abc', 'target_date' => '2026-11-20'])
            ->assertSessionHasErrors(['book_id' => '選択された書籍は存在しません。']);

        $this->actingAs($this->user)
            ->post(route('reading-plans.store'), ['book_id' => 9999, 'target_date' => '2026-11-20'])
            ->assertSessionHasErrors(['book_id' => '選択された書籍は存在しません。']);
    }

    public function test_book_id_is_not_required_on_update(): void
    {
        $plan = $this->createPlan();

        $this->actingAs($this->user)
            ->put(route('reading-plans.update', $plan), ['target_date' => '2026-11-25'])
            ->assertSessionDoesntHaveErrors('book_id');
    }

    // ---- 2. target_date（b〜d） ----

    public function test_b_target_date_required(): void
    {
        $plan = $this->createPlan();

        $this->submitCreate(['book_id' => Book::factory()->create(['user_id' => $this->user->id])->id, 'target_date' => ''])
            ->assertOk()->assertSee('期日は必須です。');
        $this->submitEdit($plan, ['target_date' => ''])
            ->assertOk()->assertSee('期日は必須です。');
    }

    public function test_c_target_date_invalid_format(): void
    {
        $plan = $this->createPlan();

        $this->submitCreate(['book_id' => Book::factory()->create(['user_id' => $this->user->id])->id, 'target_date' => '2026-13-45'])
            ->assertOk()->assertSee('期日は有効な日付形式で入力してください。');
        $this->submitEdit($plan, ['target_date' => '2026-13-45'])
            ->assertOk()->assertSee('期日は有効な日付形式で入力してください。');
    }

    public function test_d_target_date_before_today(): void
    {
        $plan = $this->createPlan();

        $this->submitCreate(['book_id' => Book::factory()->create(['user_id' => $this->user->id])->id, 'target_date' => '2026-11-14'])
            ->assertOk()->assertSee('期日は今日以降の日付を指定してください。');
        $this->submitEdit($plan, ['target_date' => '2026-11-14'])
            ->assertOk()->assertSee('期日は今日以降の日付を指定してください。');
    }

    // ---- 3. 重複制御（e） ----

    public function test_e_duplicate_in_progress_plan_on_create(): void
    {
        foreach ([ReadingPlanStatus::NotCompleted, ReadingPlanStatus::Reading] as $status) {
            ReadingPlan::query()->delete();
            $this->createPlan(['status' => $status]);

            $this->submitCreate(['book_id' => $this->book->id, 'target_date' => '2026-11-20'])
                ->assertOk()->assertSee('この書籍は既に進行中の読書計画が存在します。');
            $this->assertSame(1, ReadingPlan::count());
        }
    }

    public function test_create_allowed_when_only_completed_or_other_users_plans_exist(): void
    {
        $this->createPlan(['status' => ReadingPlanStatus::Completed]);
        $this->createPlan(['user_id' => User::factory()->create()->id]);

        $this->actingAs($this->user)
            ->post(route('reading-plans.store'), ['book_id' => $this->book->id, 'target_date' => '2026-11-20'])
            ->assertSessionHasNoErrors();
    }

    public function test_e_duplicate_in_progress_plan_on_edit(): void
    {
        // 既に読了した計画を編集しようとしたとき、同じ書籍で別の進行中計画がある
        $completed = $this->createPlan(['status' => ReadingPlanStatus::Completed]);
        $this->createPlan(['status' => ReadingPlanStatus::Reading]);

        $this->submitEdit($completed, ['target_date' => '2026-11-25'])
            ->assertOk()->assertSee('この書籍は既に進行中の読書計画が存在します。');
        $this->assertSame('2026-11-20', $completed->fresh()->target_date->format('Y-m-d'));
    }
}
