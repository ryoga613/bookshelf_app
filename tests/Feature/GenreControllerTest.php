<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GenreControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    public function test_user_can_see_genre_index(): void
    {
        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
        $response = $this->actingAs($user)->get('/genres');

        $response->assertStatus(200);
        $response->assertViewIs('genres.index');
        $response->assertViewHas('genres');
    }

    public function test_user_can_see_genre_create()
    {
        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
        $response = $this->actingAs($user)->get(route('genres.create'));

        $response->assertStatus(200);
        $response->assertViewIs('genres.create');
    }

    public function test_can_show_genre_detail()
    {
        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $genre = Genre::create(['name' => '旅行']);
        $response = $this->actingAs($user)->get(route('genres.show', [$genre->id]));

        $response->assertViewIs('genres.show');
    }

    public function test_user_can_see_genre_edit()
    {
        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $genre = Genre::create(['name' => '旅行']);
        $response = $this->actingAs($user)->get(route('genres.edit', $genre));

        $response->assertStatus(200);
        $response->assertViewIs('genres.edit');
    }

    public function test_user_can_see_genre_store()
    {
        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $genre = Genre::create(['name' => '旅行']);
        $response = $this->actingAs($user)->post(route('genres.store', $genre));

        $this->assertDatabaseHas('genres', ['name' => '旅行']);

    }

    public function test_user_can_genre_update()
    {
        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $genre1 = Genre::create(['name' => '旅行']);
        $genre2 = ['name' => 'ビジネス'];
        $this->assertDatabaseHas('genres', ['name' => '旅行']);

        $response = $this->actingAs($user)->put(route('genres.update', $genre1), $genre2);

        $this->assertDatabaseHas('genres', ['name' => 'ビジネス']);
    }

    public function test_user_can_genre_destroy()
    {
        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $genre = Genre::create(['name' => '旅行']);
        $this->actingAs($user)->delete(route('genres.destroy', $genre));
        $this->assertDatabaseMissing('genres', ['name' => '旅行']);

    }
}
