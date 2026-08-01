<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $allReviewIds = Review::pluck('id');
        $allUserIds = User::pluck('id');

        if ($allReviewIds->isEmpty() || $allUserIds->isEmpty()) {
        return;
    }
        $possibleLikes = collect();

    foreach ($allReviewIds as $reviewId) {
        foreach ($allUserIds as $userId) {
            $possibleLikes->push([
                'review_id' => $reviewId,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    $takeCount = min(30, $possibleLikes->count());

    $selectedLikes = $possibleLikes->shuffle()->take($takeCount)->toArray();

    ReviewLike::insert($selectedLikes);
    }
}

    

