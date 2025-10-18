<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleStat;
use Carbon\Carbon;

class ArticleService
{
    public static function storeData($title, $link, $siteId, $data): void
    {
        $article = Article::query()->where('link', $link)->first();

        if (!$article) {
            $article = Article::query()->create([
                'title' => $title,
                'link' => $link,
                'site_id' => $siteId,
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'text' => $data['text'] ?? null,
            ]);
        }

        $articleStat = ArticleStat::query()->where('article_id', $article->id)->first();

        $viewsSpeed = null;
        if ($articleStat) {
            $hoursPassed = Carbon::now()->floatDiffInHours($articleStat->created_at);

            if ($hoursPassed > 0) {
                $viewsSpeed = ($data['views'] - $articleStat->views) / $hoursPassed;
            }
        }

        if (!$articleStat || $articleStat->created_at < now()->subHour()) {
            ArticleStat::query()->create([
                'article_id' => $article->id,
                'views' => $data['views'] ?? 0,
                'views_speed' => $viewsSpeed,
            ]);
        }
    }
}
