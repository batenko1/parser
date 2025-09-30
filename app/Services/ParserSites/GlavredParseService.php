<?php

namespace App\Services\ParserSites;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class GlavredParseService implements ParserSitesInterface
{
    public function parse(string $link): int|string
    {
        try {
            // 1. Загружаем страницу
            $response = Http::get($link);

            if (! $response->successful()) {
                return 'нет данных (HTTP ' . $response->status() . ')';
            }

            $html = $response->body();

            // 2. Парсим HTML
            $crawler = new Crawler($html);

            // 3. Находим первый элемент .js-views внутри .article__views
            $viewsNode = $crawler->filter('.article__views .js-views')->first();

            if ($viewsNode->count() > 0) {
                $views = trim($viewsNode->text());
                return (int) $views;
            }

            return 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
