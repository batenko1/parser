<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;

class RecalculateStatsCommand extends Command
{
    protected $signature = 'stats:recalculate';
    protected $description = 'Recalculate speed_x and is_very_fast for all statistics';

    public function handle()
    {
        Article::query()
            ->with(['stats' => fn($q) => $q->orderBy('created_at')])
            ->chunk(200, function ($articles) {
                foreach ($articles as $article) {
                    $this->recalculateArticle($article);
                }
            });

        $this->info("✔ Готово! Все статьи пересчитаны.");
    }

    private function recalculateArticle(Article $article)
    {
        $stats = $article->stats;

        if ($stats->count() < 1) {
            return;
        }

        $site = $article->site;

        $article->is_very_fast = false;
        $article->speed_x = 0;

        foreach ($stats as $index => $stat) {
            if ($index == 0) {
                if ($site->very_fast_value && $stat->views > $site->very_fast_value) {
                    $article->is_very_fast = true;
                }
                continue;
            }

            $prev = $stats[$index - 1];

            $hoursPassed = $prev->created_at->floatDiffInHours($stat->created_at);

            if ($hoursPassed <= 0.0167) {
                continue;
            }

            $viewsDiff = max(0, $stat->views - $prev->views);

            $viewsSpeed = $hoursPassed > 0 ? $viewsDiff / $hoursPassed : null;

            if (!$viewsSpeed || !$site->speed_x) continue;

            $ratio = $viewsSpeed / $site->speed_x;
            $times = 0;

            if ($ratio >= 1 && $ratio < 3) {
                $times = 1;
            } elseif ($ratio >= 3 && $ratio < 5) {
                $times = 2;
            } elseif ($ratio >= 5) {
                $times = 3;
            }

            if ($times > 0 && (!$article->speed_x || $article->speed_x < $times)) {
                $article->speed_x = $times;
            }
        }

        $article->save();
    }
}
