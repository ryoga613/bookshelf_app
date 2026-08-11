<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreBookRequest;
use App\Http\Requests\Api\UpdateBookRequest;
use App\Models\Book;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $books = Book::all();

        return response()->json([
            'data' => $books,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookRequest $request)
    {
        $validated = $request->validated();

        $book = Book::create($validated);

        return response()->json([
            'message' => '書籍を登録しました',
            'data' => $book,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $book = Book::find($id);

        if (! $book) {
            return response()->json([
                'message' => '書籍が見つかりません',
            ], 404);
        }

        return response()->json([
            'data' => $book,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookRequest $request, string $id)
    {
        $book = Book::find($id);

        if (! $book) {
            return response()->json([
                'message' => '書籍が見つかりません',
            ], 404);
        }

        $validated = $request->validated();

        $book->update($validated);

        return response()->json([
            'message' => '書籍を更新しました',
            'data' => $book,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(string $id)
    {
        $book = Book::find($id);

        if (! $book) {
            return response()->json([
                'message' => '書籍が見つかりません',
            ], 404);
        }

        $book->delete();

        return response()->json([
            'message' => '書籍が削除されました',
        ], 204);
    }
}
