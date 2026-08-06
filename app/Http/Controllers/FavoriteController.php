<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\User;

class FavoriteController extends Controller
{
    public function index()
    {
        $books = Book::whereHas('favorites', function ($query) {
            $query->where('user_id', auth()->id());
        })->paginate(10);
        return view('favorites.index', compact('books'));
    }

    public function toggle(Book $book)
    {
        
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $Favorite = $user->favoriteBooks()->where('book_id', $book->id)->exists();

        if ($Favorite) {
            $user->favoriteBooks()->detach($book->id);
        } else {
            $user->favoriteBooks()->attach($book->id);
        }

        return redirect()->back();
    }
}
