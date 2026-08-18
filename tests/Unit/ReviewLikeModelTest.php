<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\ReviewLike;
use App\Models\Review;
class ReviewLikeModelTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    use RefreshDatabase;

    public function test_can_get_user(): void
    {

        $user = User::factory()->create();
        $review = Review::factory()->create();
        $reviewLike = ReviewLike::factory()->create([
            'user_id' =>$user->id,
        ]);

        $reviewlikedUser = $reviewLike->user;
        $this->assertTrue($reviewlikedUser->is($user));
    }

    public function test_can_get_review(): void
    {

        $review = Review::factory()->create();
        $review = Review::factory()->create();
        $reviewLike = ReviewLike::factory()->create([
            'review_id' =>$review->id,
        ]);

        $likedreview = $reviewLike->review;
        $this->assertTrue($likedreview->is($review));
    }

}
