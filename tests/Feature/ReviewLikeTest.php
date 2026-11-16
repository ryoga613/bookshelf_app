<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * レビューへのいいね（追加・解除・トグル・ゲスト制限）
 */
class ReviewLikeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Review $review;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->review = Review::factory()->create();
    }

    private function like()
    {
        return $this->actingAs($this->user)
            ->from(route('books.show', $this->review->book_id))
            ->post(route('reviews.like', $this->review));
    }

    public function test_add_like(): void
    {
        $this->like()->assertRedirect(route('books.show', $this->review->book_id));

        $this->assertDatabaseHas('review_likes', ['user_id' => $this->user->id, 'review_id' => $this->review->id]);
    }

    public function test_remove_like(): void
    {
        $this->user->likedReviews()->attach($this->review->id);

        $this->like()->assertRedirect(route('books.show', $this->review->book_id));

        $this->assertDatabaseMissing('review_likes', ['user_id' => $this->user->id, 'review_id' => $this->review->id]);
    }

    public function test_toggle_switches_on_and_off_without_duplicates(): void
    {
        $this->like();
        $this->assertDatabaseCount('review_likes', 1);

        $this->like();
        $this->assertDatabaseCount('review_likes', 0);

        $this->like();
        $this->assertDatabaseCount('review_likes', 1);
    }

    public function test_like_count_is_shown_on_book_page(): void
    {
        User::factory()->create()->likedReviews()->attach($this->review->id);
        $this->like();

        $this->actingAs($this->user)->get(route('books.show', $this->review->book_id))
            ->assertOk()
            ->assertSee('いいね済み (2)');
    }

    public function test_toggle_does_not_affect_other_users_likes(): void
    {
        $other = User::factory()->create();
        $other->likedReviews()->attach($this->review->id);

        $this->like();
        $this->like();

        $this->assertDatabaseHas('review_likes', ['user_id' => $other->id, 'review_id' => $this->review->id]);
    }

    public function test_like_returns_404_for_missing_review(): void
    {
        $this->actingAs($this->user)->post(route('reviews.like', 9999))->assertNotFound();
    }

    public function test_guest_cannot_like(): void
    {
        $this->post(route('reviews.like', $this->review))->assertRedirect(route('login'));

        $this->assertDatabaseCount('review_likes', 0);
    }
}
