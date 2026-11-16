<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Support\Facades\Redirect;

class ReviewController extends Controller
{
    // likedByUsers
    public function store(StoreReviewRequest $request, string $id)
    {
        $validated = $request->validated();

        $validated['book_id'] = $id;
        $validated['user_id'] = auth()->id();
        Review::create($validated);
        $book = Book::findOrFail($id);

        return Redirect::route('books.show', compact('book'))->with('success', 'レビューが作成されました。');
    }

    public function edit(string $id)
    {
        $review = Review::findOrFail($id);
        $this->authorize('update', $review);

        return View('reviews.edit', compact('review'));
    }

    public function update(UpdateReviewRequest $request, string $id)
    {
        $review = Review::findOrFail($id);
        $this->authorize('update', $review);

        $review->update($request->validated());
        $book = $review->book;

        return Redirect::route('books.show', ['book' => $book->id])->with('success', 'レビューが更新されました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(string $id)
    {
        $review = Review::findOrFail($id);
        $this->authorize('delete', $review);

        $review->delete();
        $book = $review->book;

        return Redirect::route('books.show', ['book' => $book->id])->with('success', 'レビューが削除されました。');
    }
}
