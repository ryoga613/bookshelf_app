<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Book;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $books = Book::all();

        foreach ($users as $user) {
            // 1ユーザーあたり3〜5冊（書籍の総数を超えないように調整）
            $favoriteCount = min(random_int(3, 5), $books->count());
 
            $favoriteBookIds = $books
                ->random($favoriteCount)
                ->pluck('id')
                ->all();
 
            // 既存のお気に入りを維持したまま追加する
            $user->favoriteBooks()->syncWithoutDetaching($favoriteBookIds);
        }
    }
}
