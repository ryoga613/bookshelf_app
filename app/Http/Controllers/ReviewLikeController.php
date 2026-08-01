<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReviewLike;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use App\Models\Book;

class ReviewLikeController extends Controller
{
    public function store(string $id)
    {
        $review = Review::findOrFail($id);
        $review_id = $review->id;
        $user_id = auth()->id();
        $data = [
            'user_id'=>$user_id,
            'review_id'=>$review_id,
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
