<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\ReviewLike;

class ReviewLikeController extends Controller
{
    public function store(string $id)
    {
        $review = Review::findOrFail($id);
        $review_id = $review->id;
        $user_id = auth()->id();
        $data = [
            'user_id' => $user_id,
            'review_id' => $review_id,
        ];

        $like = ReviewLike::where($data)->first();

        if ($like) {
            $like->delete();
        } else {
            ReviewLike::create($data);
        }

        return back();

    }
}
