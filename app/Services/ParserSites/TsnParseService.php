<?php

namespace App\Services\ParserSites;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class TsnParseService implements ParserSitesInterface
{
    public function parse(string $link): array
    {
        $data = [
            'meta_title' => '',
            'meta_description' => '',
            'text' => '',
            'views' => 0,
        ];

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36',
                'Accept-Language' => 'uk-UA,uk;q=0.9,en;q=0.8',
            ])->get($link);

            if (!$response->successful()) {
                return $data;
            }

            $html = $response->body();
            $crawler = new Crawler($html);

            $metaTitle = $crawler->filterXPath('//meta[@property="og:title"]')->attr('content') ?? '';
            $metaDescription = $crawler->filterXPath('//meta[@name="description"]')->attr('content') ?? '';

            if (empty($metaTitle) && $crawler->filter('title')->count()) {
                $metaTitle = $crawler->filter('title')->text();
            }

            $data['meta_title'] = trim($metaTitle);
            $data['meta_description'] = trim($metaDescription);

            $viewsNode = $crawler->filter('.c-entry__views')->first();
            if ($viewsNode->count()) {
                $viewsText = trim($viewsNode->text());
                $viewsText = mb_strtolower($viewsText);
                $viewsText = str_replace([' ', ','], ['', '.'], $viewsText);

                if (preg_match('/([\d.]+)\s*(k|т|тис|тыс)/u', $viewsText, $matches)) {
                    $data['views'] = (int) round(((float)$matches[1]) * 1000);
                } elseif (preg_match('/([\d.]+)\s*(m|млн)/u', $viewsText, $matches)) {
                    $data['views'] = (int) round(((float)$matches[1]) * 1_000_000);
                } else {
                    $data['views'] = (int) preg_replace('/[^\d]/', '', $viewsText);
                }
            }

            $articleNode = $crawler->filter('.c-prose.c-post__inner')->first();
            if ($articleNode->count()) {
                $articleText = trim(preg_replace('/\s+/', ' ', strip_tags($articleNode->html())));
                $data['text'] = $articleText;
            }

        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }
}
