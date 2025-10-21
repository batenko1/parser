<?php

namespace App\Services;

use App\Jobs\UpdateArticleStatJob;
use App\Models\Article;
use App\Models\ArticleStat;

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

        $lastStat = ArticleStat::query()
            ->where('article_id', $article->id)
            ->latest('id')
            ->first();

        if (!$lastStat) {

            ArticleStat::query()->create([
                'article_id' => $article->id,
                'views' => $data['views'] ?? 0,
                'views_speed' => null,
            ]);

            self::scheduleUpdates($article->id);

        }

    }

    protected static function scheduleUpdates(int $articleId): void
    {
        $periods = [
            45,      // 0:45h
            90,      // 1.5h
            180,     // 3h
            360,     // 6h
            720,     // 12h
            1440,    // 24h
            2880,    // 48h
        ];

        foreach ($periods as $minutes) {
            sleep(2);
            UpdateArticleStatJob::dispatch($articleId)->delay(now()->addMinutes($minutes));
        }
    }
}
