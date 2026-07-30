<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Models\Genre;

class GenreController extends Controller
{
    public function index()
    {
        $genres = Genre::withCount('books')->get();

        return view('genres.index', compact('genres'));
    }

    public function create()
    {
        return View('genres.create');
    }

    public function show(string $id)
    {
        $genre = Genre::withCount('books')->findOrFail($id);
        $books = $genre->books()->latest()->paginate(10);

        return View('genres.show', compact('genre', 'books'));
    }

    public function edit(string $id)
    {
        $genre = Genre::withCount('books')->findOrFail($id);

        return View('genres.edit', compact('genre'));

    }

    public function update(UpdateGenreRequest $request, string $id)
    {
        $validated = $request->validated();
        $genre = Genre::findOrFail($id);
        $genre->update($validated);

        return redirect(route('genres.index'));
    }

    public function store(StoreGenreRequest $request)
    {
        $validated = $request->validated();
        Genre::create($validated);
        return redirect(route('genres.index'));
    }

    public function delete(string $id)
    {
        $genre = Genre::findOrFail($id);
        $genre->delete();
        return redirect(route('genres.index'));
    }
}
