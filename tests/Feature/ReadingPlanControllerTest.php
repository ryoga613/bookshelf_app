<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReadingPlanControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    public function test_can_see_reading_plan_index(): void
    {
        $user = User::create([
            'name' => 'testUser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
        $response = $this->actingAs($user)->get('/reading-plans');

        $response->assertStatus(200);
        $response->assertViewIs('reading-plans.index');
    }

    public function test_can_see_create_reading_plans()
    {
        $user = User::create([
            'name' => 'testUser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($user)->get('/reading-plans/create');

        $response->assertStatus(200);
        $response->assertViewIs('reading-plans.create');
    }

    public function test_can_store_reading_plans()
    {
        $user = User::create([
            'name' => 'testUser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
        $genre = Genre::create(['name' => '小説']);

        // 2. 本を作成し、ジャンルを紐付け
        $book = Book::create([
            'user_id' => $user->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_at' => '1905-01-01',
            'description' => '猫の視点から人間社会を風刺的に描いた、夏目漱石の代表作。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
            'genre' => $genre->id,
        ]);

        $postData = [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => 0,
            'target_date' => Carbon::today()->copy()->addDays(2),
        ];

        $this->actingAs($user)->post('/reading-plans', $postData);

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => 0,
        ]);
    }

    public function test_can_status_complete()
    {
        $user = User::create([
            'name' => 'testUser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
        $genre = Genre::create(['name' => '小説']);

        // 2. 本を作成し、ジャンルを紐付け
        $book = Book::create([
            'user_id' => $user->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_at' => '1905-01-01',
            'description' => '猫の視点から人間社会を風刺的に描いた、夏目漱石の代表作。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
            'genre' => $genre->id,
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => 0,
            'target_date' => Carbon::today()->copy()->addDays(2),
        ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.complete', $readingPlan->id));

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::Completed,
        ]);
    }

    public function test_can_see_reading_plan_edit()
    {
        $user = User::create([
            'name' => 'testUser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
        $genre = Genre::create(['name' => '小説']);

        // 2. 本を作成し、ジャンルを紐付け
        $book = Book::create([
            'user_id' => $user->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_at' => '1905-01-01',
            'description' => '猫の視点から人間社会を風刺的に描いた、夏目漱石の代表作。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
            'genre' => $genre->id,
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => 0,
            'target_date' => Carbon::today()->copy()->addDays(2),
        ]);

        $response = $this->actingAs($user)->get(route('reading-plans.edit', $readingPlan));

        $response->assertViewIs('reading-plans.edit');
    }

    public function test_can_reading_plan_update()
    {
        $user = User::create([
            'name' => 'testUser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
        $genre = Genre::create(['name' => '小説']);

        // 2. 本を作成し、ジャンルを紐付け
        $book = Book::create([
            'user_id' => $user->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_at' => '1905-01-01',
            'description' => '猫の視点から人間社会を風刺的に描いた、夏目漱石の代表作。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
            'genre' => $genre->id,
        ]);

        $readingPlan1 = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => Carbon::today()->addDays(2)->format('Y-m-d'),
        ]);

        // 💡 UpdateReadingPlanRequest が要求するフィールドをすべて網羅する
        $updateData = [
            'book_id' => $book->id,
            'target_date' => Carbon::today()->addDays(7)->format('Y-m-d'),
            // もし rules() に他の必須項目があればここに追加してください
        ];

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan1), $updateData);

        $response->assertRedirect(route('reading-plans.index'));

        $readingPlan1->refresh();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan1->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => Carbon::today()->addDays(7)->format('Y-m-d 00:00:00'),
        ]);
    }

    public function test_can_delete_reading_plan()
    {
        $user = User::create([
            'name' => 'testUser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
        $genre = Genre::create(['name' => '小説']);

        // 2. 本を作成し、ジャンルを紐付け
        $book = Book::create([
            'user_id' => $user->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_at' => '1905-01-01',
            'description' => '猫の視点から人間社会を風刺的に描いた、夏目漱石の代表作。',
            'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
            'genre' => $genre->id,
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => Carbon::today()->addDays(2)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)->delete(route('reading-plans.destroy', $readingPlan));

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => Carbon::today()->addDays(2)->format('Y-m-d 00:00:00'),
        ]);
    }
}
