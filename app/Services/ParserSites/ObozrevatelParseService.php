<?php

namespace App\Services\ParserSites;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class ObozrevatelParseService implements ParserSitesInterface
{
    public function parse(string $link): int|string
    {
        try {
            // 1. Загружаем страницу с заголовками, чтобы не словить 403
            $response = Http::withHeaders([
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'uk-UA,uk;q=0.9,en-US;q=0.8,en;q=0.7',
                'Referer'         => $link,
            ])->get($link);

            if (! $response->successful()) {
                return 0;
            }

            $html = $response->body();

            // 2. Парсим DOM
            $crawler = new Crawler($html);

            $viewsNode = $crawler->filter('.reaction_value')->first();

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
