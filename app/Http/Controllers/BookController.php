<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $query = Book::query();

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'LIKE', "%{$keyword}%")
                    ->orWhere('author', 'LIKE', "%{$keyword}%");
            });
        }

        if ($request->filled('genre')) {
            $genreId = $request->input('genre');

            $query->whereHas('genres', function ($q) use ($genreId) {
                $q->where('genres.id', $genreId);
            });
        }

        switch ($request->query('sort')) {

            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;

            case 'rating':
                $query
                    ->withAvg('reviews', 'rating')
                    ->orderByRaw('reviews_avg_rating IS NULL ASC')
                    ->orderBy('reviews_avg_rating', 'desc');
                break;

            case 'title':
                $query->orderBy('title', 'desc');
                break;

            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $books = $query->paginate(10);
        $genres = Genre::all();
        $unreadNotifications = auth()->check() ? auth()->user()->unreadNotifications : collect();

        return view('books.index', compact('books', 'genres', 'unreadNotifications'));
    }

    public function show(string $id)
    {
        $book = Book::with('reviews')->findOrFail($id);
       

        return View('books.show', compact('book'));
    }

    public function create()
    {
        $genres = Genre::all();

        return View('books.create', compact('genres'));
    }

    public function store(StoreBookRequest $request)
    {
        $validated = $request->validated();

        $validated['user_id'] = auth()->id();

        $book = Book::create($validated);
        $book->genres()->attach($request->genres);

        return redirect(route('books.index'));
    }

    public function edit(string $id)
    {
        $genres = Genre::all();
        $book = Book::findOrFail($id);

        return View('books.edit', compact('genres', 'book'));
    }

    public function update(UpdateBookRequest $request, string $id)
    {
        $validated = $request->validated();

        $book = Book::findOrFail($id);
        $book->update($validated);
        $book->genres()->sync($request->genres);

        return redirect(route('books.index'));
    }

    public function destroy(string $id)
    {
        $book = Book::findOrFail($id);

        $book->delete();

        return redirect(route('books.index'));
    }
}
