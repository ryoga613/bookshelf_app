<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * review_likes テーブルにいいねデータを投入する
     *
     * 各レビューに 0〜3 人のユーザーがいいねする（レビュー投稿者本人は除く）。
     * syncWithoutDetaching() を使用し、既存のいいねを維持したまま重複なく追加する。
     */
    public function run(): void
    {
        $userIds = User::pluck('id');

        foreach (Review::all() as $review) {
            // 自分のレビューにはいいねしない
            $candidateIds = $userIds->reject(fn ($id) => $id === $review->user_id);

            // 0〜3人（候補者数を超えないように調整）
            $likeCount = min(random_int(0, 3), $candidateIds->count());

            if ($likeCount === 0) {
                continue;
            }

            $likedUserIds = $candidateIds->random($likeCount)->all();

            $review->likedByUsers()->syncWithoutDetaching($likedUserIds);
        }
    }
}
