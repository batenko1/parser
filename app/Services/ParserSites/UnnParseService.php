<?php

namespace App\Services\ParserSites;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class UnnParseService implements ParserSitesInterface
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

            // --- META ---
            $metaTitle = '';
            $metaDescription = '';

            if ($crawler->filterXPath('//meta[@property="og:title"]')->count()) {
                $metaTitle = $crawler->filterXPath('//meta[@property="og:title"]')->attr('content');
            }

            if ($crawler->filterXPath('//meta[@name="description"]')->count()) {
                $metaDescription = $crawler->filterXPath('//meta[@name="description"]')->attr('content');
            }

            if (empty($metaTitle) && $crawler->filter('title')->count()) {
                $metaTitle = $crawler->filter('title')->text();
            }

            $data['meta_title'] = trim($metaTitle);
            $data['meta_description'] = trim($metaDescription);

            // --- TEXT ---
            if ($crawler->filter('div[class^="single-news-card_contentBlock__"]')->count()) {
                $textNode = $crawler->filter('div[class^="single-news-card_contentBlock__"]')->first();
                $articleText = trim(preg_replace('/\s+/', ' ', strip_tags($textNode->html())));
                $data['text'] = $articleText;
            }


            // --- VIEWS ---
            if ($crawler->filter('p[class^="single-news-card_tagDateInfoItem__"]')->count()) {
                $viewsHtml = $crawler->filter('p[class^="single-news-card_tagDateInfoItem__"]')->eq(1)->html();


                if (preg_match('/<!--\s*-->\s*(\d+)\s*<!--\s*-->/u', $viewsHtml, $m)) {
                    $data['views'] = (int) $m[1];
                }
            }

        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }
}
