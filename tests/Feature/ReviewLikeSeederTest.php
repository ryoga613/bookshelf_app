<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\ReviewLikeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReviewLikeSeederTest extends TestCase
{
    use RefreshDatabase;

    private function createReviews(int $count)
    {
        $users = User::factory(5)->create();
        $book = Book::factory()->create(['user_id' => $users->first()->id]);

        return collect(range(1, $count))->map(fn ($i) => Review::create([
            'user_id' => $users[$i % $users->count()]->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => "テストレビュー{$i}",
        ]));
    }

    public function test_each_review_gets_0_to_3_likes_excluding_author(): void
    {
        $reviews = $this->createReviews(20);

        $this->seed(ReviewLikeSeeder::class);

        foreach ($reviews as $review) {
            $likedUserIds = $review->likedByUsers()->pluck('users.id');
            $this->assertLessThanOrEqual(3, $likedUserIds->count());
            $this->assertNotContains($review->user_id, $likedUserIds);
        }

        $this->assertSame(0, DB::table('review_likes')->whereNull('created_at')->count());
    }

    public function test_keeps_existing_likes(): void
    {
        $review = $this->createReviews(1)->first();
        $liker = User::where('id', '!=', $review->user_id)->first();
        $review->likedByUsers()->attach($liker->id);

        $this->seed(ReviewLikeSeeder::class);

        $this->assertTrue($review->likedByUsers()->where('users.id', $liker->id)->exists());
    }
}
