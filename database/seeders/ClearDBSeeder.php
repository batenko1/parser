<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleStat;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClearDBSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Article::query()->truncate();
        ArticleStat::query()->truncate();
    }
}
