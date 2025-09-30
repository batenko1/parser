<?php

namespace App\Services\ParserSites;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class TsnParseService implements ParserSitesInterface
{
    public function parse(string $link): int|string
    {
        try {
            $response = Http::get($link);

            if (!$response->successful()) {
                return 0;
            }

            $html = $response->body();

            $crawler = new Crawler($html);

            $viewsNode = $crawler->filter('.c-entry__views')->first();

            if ($viewsNode->count() > 0) {
                $views = trim($viewsNode->text());
                return (int)$views;
            }

            return 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
