<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Review;

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
            // 1. 「自分のレビューを書いたユーザーID」を除外したユーザーリストを作成
            $availableUserIds = $allUserIds->reject(function ($userId) use ($review) {
                return $userId === $review->user_id;
            });

            // 2. 残りのユーザーからランダムに 0〜3人 選出
            $likeCount = rand(0, min(3, $availableUserIds->count()));
            
            if ($likeCount > 0) {
                $randomUserIds = $availableUserIds->random($likeCount)->toArray();

                // 3. Reviewモデルから likes（UserへのbelongsToMany）を介して syncWithoutDetaching
                $review->likes()->syncWithoutDetaching($randomUserIds);
            }
        }
    }
}
