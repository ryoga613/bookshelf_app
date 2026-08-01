<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Support\Facades\Auth;

class MyReportController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // 自分のレビューを取得（ジャンル集計のためbook.genresもEager Load）
        $reviews = Review::with('book.genres')
            ->where('user_id', $userId)
            ->get();

        // --- 基本統計 ---
        $totalReviews = Auth::user()->reviews->count();
        $booksRead = $reviews->pluck('book_id')->unique()->count();
        $averageRating = $totalReviews > 0 ? $reviews->avg('rating') : 0;

        // --- 評価分布（★1〜★5、インデックス0〜4） ---
        // 1〜5点それぞれの件数を数え、添字0〜4のCollectionにする
        $ratingDistribution = collect(range(1, 5))->map(function ($rating) use ($reviews) {
            return $reviews->where('rating', $rating)->count();
        })->values();

        // --- 高評価書籍TOP5（評価4以上、自分のレビューの中から） ---
        $topRatedBooks = $reviews
            ->where('rating', '>=', 4)
            ->sortByDesc('rating')
            ->take(5)
            ->map(function ($review) {
                return [
                    'id' => $review->book->id,
                    'title' => $review->book->title,
                    'author' => $review->book->author,
                    'rating' => $review->rating,
                ];
            })
            ->values();

        // --- ジャンル別評価傾向TOP5 ---
        $genreRatings = $reviews
            ->flatMap(function ($review) {
                // 1件のレビューが複数ジャンルに属する書籍の場合、ジャンルごとに展開
                return $review->book->genres->map(function ($genre) use ($review) {
                    return [
                        'genre_id' => $genre->id,
                        'genre_name' => $genre->name,
                        'rating' => $review->rating,
                    ];
                });
            })
            ->groupBy('genre_id')
            ->map(function ($group) {
                return [
                    'id' => $group->first()['genre_id'],
                    'name' => $group->first()['genre_name'],
                    'count' => $group->count(),
                    'average_rating' => $group->avg('rating'),
                ];
            })
            ->sortByDesc('average_rating')
            ->take(5)
            ->values();

        $stats = [
            'summary' => [
                'total_reviews' => $totalReviews,
                'books_read' => $booksRead,
                'average_rating' => $averageRating,
            ],
            'rating_distribution' => $ratingDistribution,
            'top_rated_books' => $topRatedBooks,
            'genre_ratings' => $genreRatings,
        ];

        return view('reports.index', compact('stats'));
    }
}
