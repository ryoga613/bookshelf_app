<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Genre;

class GenreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $genres = [
            ['name'=>'小説'],
            ['name'=>'ビジネス'],
            ['name'=>'技術書'],
            ['name'=>'自己啓発'],
            ['name'=>'エッセイ'],
            ['name'=>'歴史'],
            ['name'=>'科学'],
            ['name'=>'芸術'],
            ['name'=>'料理'],
            ['name'=>'旅行'],
            ];

        foreach($genres as $genre)
            {
                Genre::create([
                    'name'=> $genre['name']
                ]);
            }
    }
}


// 内容: 「」「ビジネス」「技術書」「自己啓発」「エッセイ」「歴史」「科学」「芸術」「料理」「旅行」
