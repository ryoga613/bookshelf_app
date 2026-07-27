<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reviews = Review::all();
        $allUserIds = User::pluck('id');

        // レビューやユーザーが存在しない場合は処理をスキップ
        if ($reviews->isEmpty() || $allUserIds->isEmpty()) {
            return;
        }

        foreach ($reviews as $review) {
            $availableUserIds = $allUserIds->reject(function ($userId) use ($review) {
                return $userId === $review->user_id;
            });

            $likeCount = rand(0, min(3, $availableUserIds->count()));

            if ($likeCount > 0) {
                $randomUserIds = $availableUserIds->random($likeCount)->toArray();

                $review->likedByUsers()->syncWithoutDetaching($randomUserIds);
            }
        }
    }
}
