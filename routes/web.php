<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\MyReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReviewLikeController;
use GuzzleHttp\Middleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {

    // bookコントローラーのルート
    Route::get('books/', [BookController::class, 'index'])->name('books.index');
    Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
    Route::get('/books/show/{book}', [BookController::class, 'show'])->name('books.show');
    Route::post('/books', [BookController::class, 'store'])->name('books.store');
    Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
    Route::put('/books/{book}', [BookController::class, 'update'])->name('books.update');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');

    // reviewコントローラーのルート
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::get('/reviews/{review}', [ReviewController::class, 'show'])->name('reviews.show');
    Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])->name('reviews.edit');
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'delete'])->name('reviews.destroy');

    // review_likeコントローラーのルート
    Route::post('/reviews/{review}/like', [ReviewLikeController::class, 'store'])->name('reviews.like');

    // genreコントローラーのルート
    Route::get('/genres', [GenreController::class, 'index'])->name('genres.index');
    Route::get('/genres/create', [GenreController::class, 'create'])->name('genres.create');
    Route::post('/genres', [GenreController::class, 'store'])->name('genres.store');
    Route::get('/genres/{genre}', [GenreController::class, 'show'])->name('genres.show');
    Route::put('/genres/{genre}', [GenreController::class, 'update'])->name('genres.update');
    Route::get('/genres/{genre}/edit', [GenreController::class, 'edit'])->name('genres.edit');
    Route::delete('/genres/{genre}', [GenreController::class, 'delete'])->name('genres.destroy');

    // my_reportコントローラーのルート
    Route::get('/reports', [MyReportController::class, 'index'])->name('reports.index');

    // 準備中
    Route::get('/ranking', function () {
        return '準備中';
    })->name('ranking.index');
    Route::get('/favorite', function () {
        return '準備中';
    })->name('favorites.index');

    Route::get('/reading-plans', function () {
        return '準備中';
    })->name('reading-plans.index');
    Route::get('/notifications', function () {
        return '準備中';
    })->name('notifications.index');
    Route::post('/books/{book}/favorites', function () {
        return '準備中';
    })->name('favorites.toggle');
});
