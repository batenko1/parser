<?php

namespace App\Services\ParserSites;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Str;

class UnianParseService implements ParserSitesInterface
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
            if (!preg_match('/-(\d+)\.html$/', $link, $matches)) {
                return $data;
            }

            $newsId = $matches[1];

            $randomCookie = 'cf_clearance=' . Str::random(6) . '; another_cookie=' . Str::random(6) . ';';

            $jsonResponse = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:129.0) Gecko/20100101 Firefox/129.0',
                'Accept' => 'application/json, text/javascript, */*; q=0.01',
                'X-Requested-With' => 'XMLHttpRequest',
                'Referer' => $link,
                'Cookie' => $randomCookie,
            ])->get("https://www.unian.ua/ajax/views/{$newsId}");

            if ($jsonResponse->successful()) {
                $data['views'] = (int) $jsonResponse->json('views');
            }

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

            if (empty($metaTitle)) {
                $metaTitle = $crawler->filter('title')->first()->text('') ?? '';
            }

            $data['meta_title'] = trim($metaTitle);
            $data['meta_description'] = trim($metaDescription);

            $articleNode = $crawler->filter('.article-text')->first();

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
