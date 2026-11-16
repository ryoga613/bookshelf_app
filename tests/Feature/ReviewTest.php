<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * レビュー（投稿・編集・削除、投稿者のみ操作可・ゲスト制限）
 */
class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private User $other;

    private Book $book;

    private Review $review;

    protected function setUp(): void
    {
        parent::setUp();
        $this->author = User::factory()->create();
        $this->other = User::factory()->create();
        $this->book = Book::factory()->create();
        $this->review = Review::factory()->create([
            'user_id' => $this->author->id,
            'book_id' => $this->book->id,
            'rating' => 3,
            'comment' => '元のコメント',
        ]);
    }

    // ---- 投稿 ----

    public function test_user_can_post_review(): void
    {
        $this->actingAs($this->other)
            ->post(route('reviews.store', $this->book), ['rating' => 5, 'comment' => 'とても良かった'])
            ->assertRedirect(route('books.show', $this->book))
            ->assertSessionHas('success', 'レビューが作成されました。');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->other->id,
            'book_id' => $this->book->id,
            'rating' => 5,
            'comment' => 'とても良かった',
        ]);
    }

    public function test_post_review_validation(): void
    {
        $this->actingAs($this->other)
            ->from(route('books.show', $this->book))
            ->post(route('reviews.store', $this->book), ['rating' => 6, 'comment' => ''])
            ->assertRedirect(route('books.show', $this->book))
            ->assertSessionHasErrors([
                'rating' => '評価は5以下で入力してください。',
                'comment' => 'コメントは必須です。',
            ]);

        $this->assertDatabaseCount('reviews', 1);
    }

    // ---- 編集 ----

    public function test_author_can_see_edit_page(): void
    {
        $this->actingAs($this->author)
            ->get(route('reviews.edit', $this->review))
            ->assertOk()
            ->assertViewIs('reviews.edit')
            ->assertViewHas('review', fn (Review $review) => $review->is($this->review))
            ->assertSee('元のコメント');
    }

    public function test_author_can_update_review(): void
    {
        $this->actingAs($this->author)
            ->put(route('reviews.update', $this->review), ['rating' => 4, 'comment' => '読み直したら良かった'])
            ->assertRedirect(route('books.show', $this->book))
            ->assertSessionHas('success', 'レビューが更新されました。');

        $this->assertDatabaseHas('reviews', ['id' => $this->review->id, 'rating' => 4, 'comment' => '読み直したら良かった']);
    }

    public function test_update_review_validation(): void
    {
        $this->actingAs($this->author)
            ->put(route('reviews.update', $this->review), ['rating' => 0, 'comment' => str_repeat('あ', 1001)])
            ->assertSessionHasErrors(['rating', 'comment']);

        $this->assertDatabaseHas('reviews', ['id' => $this->review->id, 'comment' => '元のコメント']);
    }

    // ---- 削除 ----

    public function test_author_can_delete_review(): void
    {
        $this->actingAs($this->author)
            ->delete(route('reviews.destroy', $this->review))
            ->assertRedirect(route('books.show', $this->book))
            ->assertSessionHas('success', 'レビューが削除されました。');

        $this->assertDatabaseMissing('reviews', ['id' => $this->review->id]);
    }

    // ---- 投稿者以外は 403 ----

    public function test_other_user_cannot_edit_update_or_delete(): void
    {
        $this->actingAs($this->other)->get(route('reviews.edit', $this->review))->assertForbidden();
        $this->actingAs($this->other)
            ->put(route('reviews.update', $this->review), ['rating' => 1, 'comment' => '改ざん'])
            ->assertForbidden();
        $this->actingAs($this->other)->delete(route('reviews.destroy', $this->review))->assertForbidden();

        $this->assertDatabaseHas('reviews', ['id' => $this->review->id, 'rating' => 3, 'comment' => '元のコメント']);
    }

    public function test_edit_links_are_shown_only_to_author(): void
    {
        $editUrl = route('reviews.edit', $this->review);

        $this->actingAs($this->author)->get(route('books.show', $this->book))->assertSee($editUrl);
        $this->actingAs($this->other)->get(route('books.show', $this->book))->assertDontSee($editUrl);
    }

    // ---- ゲスト ----

    public function test_guest_cannot_post_edit_update_or_delete(): void
    {
        $this->post(route('reviews.store', $this->book), ['rating' => 5, 'comment' => 'ゲスト'])->assertRedirect(route('login'));
        $this->get(route('reviews.edit', $this->review))->assertRedirect(route('login'));
        $this->put(route('reviews.update', $this->review), ['rating' => 1, 'comment' => 'ゲスト'])->assertRedirect(route('login'));
        $this->delete(route('reviews.destroy', $this->review))->assertRedirect(route('login'));

        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseHas('reviews', ['id' => $this->review->id, 'comment' => '元のコメント']);
    }

    public function test_guest_can_read_reviews_on_book_page(): void
    {
        $this->get(route('books.show', $this->book))->assertOk()->assertSee('元のコメント');
    }
}
