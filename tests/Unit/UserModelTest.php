<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Notification;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    use RefreshDatabase;

    public function test_can_get_favorite_books(): void
    {
        $user = User::factory()->create();
        $books = Book::factory()->count(2)->create();

        $user->favoriteBooks()->attach($books->pluck('id'));

        $favoriteBooks = $user->favoriteBooks;

        $this->assertCount(2, $favoriteBooks);

        $this->assertTrue($favoriteBooks->contains($books->first()));
        $this->assertTrue($favoriteBooks->contains($books->last()));
    }

    public function test_can_get_reviews(): void
    {
        $user = User::factory()->create();

        $reviews = Review::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $otherReview = Review::factory()->create();

        $userReviews = $user->reviews;

        $this->assertCount(3, $userReviews);

        foreach ($reviews as $review) {
            $this->assertTrue($userReviews->contains($review));
        }
    }

    public function test_can_get_liked_reviews(): void
    {
        $user = User::factory()->create();

        $likedReviews = Review::factory()->count(2)->create();
        $unlikedReview = Review::factory()->create();

        $user->likedReviews()->attach($likedReviews->pluck('id'));

        $result = $user->likedReviews;

        $this->assertCount(2, $result);

        $this->assertTrue($result->contains($likedReviews->first()));
        $this->assertTrue($result->contains($likedReviews->last()));
    }

    public function test_can_get_reading_plan(): void
    {
        $user = User::factory()->create();
        $plans = ReadingPlan::factory()->count(2)->create([
            'user_id' => $user->id,
        ]);

        $otherPlan = ReadingPlan::factory()->create();

        $this->assertInstanceOf(HasMany::class, $user->readingPlans());

        $userPlans = $user->readingPlans;

        $this->assertCount(2, $userPlans);
        $this->assertTrue($userPlans->contains($plans->first()));
        $this->assertTrue($userPlans->contains($plans->last()));
    }

    public function test_can_get_notifications(): void
    {
        $user = User::factory()->create();

        $notifications = Notification::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(HasMany::class, $user->notifications());

        $this->assertCount(3, $user->notifications);

        $this->assertTrue($user->notifications->contains($notifications->first()));
    }

    public function test_can_get_un_read_notification()
    {
        $user = User::factory()->create();

        $unreadNotifications = Notification::factory()->count(2)->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);

        $readNotification = Notification::factory()->create([
            'user_id' => $user->id,
            'read_at' => now(),
        ]);

        $results = $user->unreadNotifications()->get();

        $this->assertCount(2, $results);

        $this->assertTrue($results->contains($unreadNotifications->first()));
        $this->assertTrue($results->contains($unreadNotifications->last()));

        $this->assertFalse($results->contains($readNotification));
    }

    public function test_can_get_book()
    {
        $user = User::factory()->create();
        $userBooks = Book::factory()->count(2)->create([
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(HasMany::class, $user->books());

        $books = $user->books;

        $this->assertCount(2, $books);

        $this->assertTrue($books->contains($userBooks->first()));
        $this->assertTrue($books->contains($userBooks->last()));

    }

    public function test_get_genre()
    {
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        $this->assertInstanceOf(HasMany::class, $user->genres());
        $this->assertTrue($genres->contains($genres->first()));
        $this->assertTrue($genres->contains($genres->last()));
    }
}
