<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\ArticleStat;
use App\Services\ParserSites\CensorParseService;
use App\Services\ParserSites\FocusParseService;
use App\Services\ParserSites\GlavredParseService;
use App\Services\ParserSites\KorrespondentParseService;
use App\Services\ParserSites\ObozrevatelParseService;
use App\Services\ParserSites\PravdaParseService;
use App\Services\ParserSites\RadiotrekParseService;
use App\Services\ParserSites\RbcParseService;
use App\Services\ParserSites\TsnParseService;
use App\Services\ParserSites\Tv24ParseService;
use App\Services\ParserSites\UnianParseService;
use App\Services\ParserSites\VsvitiParseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateArticleStatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $articleId;

    public function __construct(int $articleId)
    {
        $this->articleId = $articleId;
    }

    public function handle(): void
    {
        $article = Article::query()->find($this->articleId);
        if (!$article) return;

        $data = $this->getArticleStat($article->site->name, $article->link);

        $lastStat = ArticleStat::query()
            ->where('article_id', $article->id)
            ->latest('id')
            ->first();

        $views = $data['views'] ?? 0;
        $viewsSpeed = null;

        if ($lastStat) {
            $hoursPassed = $lastStat->created_at->floatDiffInHours(now());
            if ($hoursPassed > 0.0167) {
                $viewsDiff = max(0, $views - $lastStat->views);
                $viewsSpeed = $hoursPassed > 0 ? $viewsDiff / $hoursPassed : null;
            }
        }

        ArticleStat::query()->create([
            'article_id' => $article->id,
            'views' => $views,
            'views_speed' => $viewsSpeed,
            'error' => $data['error'] ?? null,
        ]);
    }


    private function getArticleStat($siteName, $link): array
    {
        $data = [
            'meta_title' => '',
            'meta_description' => '',
            'text' => '',
            'views' => 0
        ];

        $service = null;
        switch ($siteName) {
            case 'Unian':
                $service = app(UnianParseService::class);
                break;
            case 'TSN';
                $service = app(TsnParseService::class);
                break;
            case 'Radiotrek':
                $service = app(RadiotrekParseService::class); //--
                break;
            case 'Glavred':
                $service = app(GlavredParseService::class);
                break;
            case 'RBC':
                $service = app(RbcParseService::class); //--
                break;
            case '24tv':
                $service = app(Tv24ParseService::class);
                break;
            case 'Censor':
                $service = app(CensorParseService::class);
                break;
            case 'Obozrevatel':
                $service = app(ObozrevatelParseService::class);
                break;
            case 'Focus':
                $service = app(FocusParseService::class);
                break;
            case 'Korrespondent':
                $service = app(KorrespondentParseService::class);
                break;
            case 'Pravda':
                $service = app(PravdaParseService::class);
                break;
            case 'Vsviti':
                $service = app(VsvitiParseService::class);
                break;
        }

        if($service) {
            $data = $service->parse($link);
        }

        return $data;
    }
}
