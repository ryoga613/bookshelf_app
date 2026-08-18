<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewModelTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    use RefreshDatabase;

    public function test_can_get_user(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        $reviewsUser = $review->user;

        $this->assertTrue($reviewsUser->is($user));
    }

    public function test_can_get_book(): void
    {
        $book = Book::factory()->create();
        $review = Review::factory()->create([
            'book_id' => $book->id,
        ]);

        $reviewsBook = $review->book;

        $this->assertTrue($reviewsBook->is($book));
    }

    public function test_can_get_liked_by_users(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $review->likedByUsers()->attach($user->id);

        $likedUsers = $review->likedByUsers;

        $this->assertTrue($likedUsers->contains($user));
    }
}
