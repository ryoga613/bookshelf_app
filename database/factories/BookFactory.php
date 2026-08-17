<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->realText(20), // 20文字程度のタイトル
            'author' => fake()->name(), // 著者名
            'isbn' => fake()->unique()->isbn13(), // 13桁の重複なしISBN
            'published_at' => fake()->optional()->date(), // ヌル許容：ランダムで日付またはnull
            'image_url' => fake()->optional()->imageUrl(640, 480, 'books'), // ヌル許容：画像URLまたはnull
            'description' => fake()->optional()->realText(100),
        ];
    }
}
