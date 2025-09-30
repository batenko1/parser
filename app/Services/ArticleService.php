<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleStat;

class ArticleService
{
    public static function storeData($title, $link, $siteId, $views = 0): void
    {
        $article = Article::query()->where('link', $link)->first();

        if (!$article) {
            $article = Article::query()->create([
                'title' => $title,
                'link' => $link,
                'site_id' => $siteId,
            ]);
        }

        $articleStat = ArticleStat::query()->where('article_id', $article->id)->first();

        if (!$articleStat || $articleStat->created_at < now()->subHour()) {
            ArticleStat::query()->create([
                'article_id' => $article->id,
                'views' => $views,
            ]);
        }
    }
}
