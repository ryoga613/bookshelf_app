<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexBookRequest;
use App\Http\Requests\Api\StoreBookRequest;
use App\Http\Requests\Api\UpdateBookRequest;
use App\Models\Book;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function index(IndexBookRequest $request)
    {

        $validated = $request->validated();
        $keyword = $validated['keyword'] ?? null;
        $genre_id = $validated['genre_id'] ?? null;

        $query = Book::with('genres');

        // 本のタイトル、著者で絞り込み
        if ($keyword) {
            // % や _ を LIKE のワイルドカードではなく文字として検索するためにエスケープする
            // （エスケープ文字は MySQL・SQLite の両方で同じように動く「!」を使う）
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $keyword).'%';

            // ジャンルの条件と組み合わせても OR が外に漏れないように、括弧でまとめる
            $query->where(function ($q) use ($pattern) {
                $q->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("author LIKE ? ESCAPE '!'", [$pattern]);
            });
        }

        // ジャンルで絞り込み（book_genre 中間テーブル経由で、指定ジャンルが紐づく書籍だけに絞る）
        if ($genre_id) {
            $query->whereHas('genres', function ($q) use ($genre_id) {
                $q->where('genres.id', $genre_id);
            });
        }

        // 1ページの件数（デフォルト20件、最大100件は IndexBookRequest で検証済み）
        $perPage = $validated['per_page'] ?? 20;

        $books = $query->orderBy('id')->paginate($perPage);

        return response()->json([
            'data' => $books->items(),
            'meta' => [
                'current_page' => $books->currentPage(),
                'last_page' => $books->lastPage(),
                'per_page' => $books->perPage(),
                'total' => $books->total(),
            ],
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookRequest $request)
    {
        $validated = $request->validated();

        $validated['user_id'] = $request->user()->id;

        $book = Book::create($validated);
        $book->genres()->sync($validated['genre_ids']);

        return response()->json([
            'message' => '書籍を登録しました',
            'data' => $book->load('genres'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $book = Book::with(['genres', 'reviews'])->find($id);

        if (! $book) {
            return $this->notFound();
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
            return $this->notFound();
        }
        $this->authorize('update', $book);

        $validated = $request->validated();

        $book->update($validated);
        $book->genres()->sync($validated['genre_ids']);

        return response()->json([
            'message' => '書籍を更新しました',
            'data' => $book->load('genres'),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(string $id)
    {
        $book = Book::find($id);

        if (! $book) {
            return $this->notFound();
        }
        $this->authorize('delete', $book);

        $book->delete();

        return response()->json([
            'message' => '書籍が削除されました',
        ], 204);
    }

    /**
     * 書籍が見つからない場合の 404 レスポンス
     */
    private function notFound()
    {
        return response()->json([
            'error' => '書籍が見つかりませんでした。',
        ], 404);
    }
}
