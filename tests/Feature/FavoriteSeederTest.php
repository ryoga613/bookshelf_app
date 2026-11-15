<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Database\Seeders\FavoriteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FavoriteSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_user_gets_3_to_5_favorites(): void
    {
        $users = User::factory(5)->create();
        Book::factory(10)->create(['user_id' => $users->first()->id]);

        $this->seed(FavoriteSeeder::class);

        foreach ($users as $user) {
            $count = $user->favoriteBooks()->count();
            $this->assertGreaterThanOrEqual(3, $count);
            $this->assertLessThanOrEqual(5, $count);
        }

        $this->assertSame(0, DB::table('favorites')->whereNull('created_at')->count());
    }

    public function test_keeps_existing_favorites_without_duplicates(): void
    {
        $user = User::factory()->create();
        $books = Book::factory(10)->create(['user_id' => $user->id]);
        $user->favoriteBooks()->attach($books->first()->id);

        $this->seed(FavoriteSeeder::class);

        $this->assertTrue($user->favoriteBooks()->where('book_id', $books->first()->id)->exists());
        $this->assertSame(
            0,
            DB::table('favorites')->select('user_id', 'book_id')
                ->groupBy('user_id', 'book_id')->havingRaw('COUNT(*) > 1')->get()->count()
        );
    }
}
