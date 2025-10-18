<?php

namespace App\Services\ParserSites;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class PravdaParseService implements ParserSitesInterface
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
            preg_match('/(\d+)\/?$/', parse_url($link, PHP_URL_PATH), $matches);
            $articleId = $matches[1] ?? null;

            if (!$articleId) {
                return $data;
            }

            $apiUrl = "https://www.pravda.com.ua/article/{$articleId}/count-view.html";
            $responseViews = Http::withHeaders([
                'Accept' => '*/*',
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36',
                'Referer' => $link,
                'Origin' => 'https://www.pravda.com.ua',
            ])->get($apiUrl);

            if ($responseViews->successful()) {
                $viewsBody = trim($responseViews->body());
                $data['views'] = (int) preg_replace('/[^\d]/', '', $viewsBody);
            }

            $response = Http::withHeaders([
                'User-Agent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36',
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

            $textNode = $crawler->filter('.post_news_body')->first();
            if ($textNode->count()) {
                $rawHtml = $textNode->html();

                $cleanHtml = preg_replace([
                    '/<script\b[^>]*>.*?<\/script>/is', // JS
                    '/<style\b[^>]*>.*?<\/style>/is',   // CSS
                    '/<!--.*?-->/s',
                    '/РЕКЛАМА:.*/ui',
                    '/Читайте також.*/ui',
                    '/Реклама:.*/ui',
                    '/function\s+[a-zA-Z0-9_]+\s*\([^)]*\)\s*\{[^}]*\}/',
                ], '', $rawHtml);

                $plainText = strip_tags($cleanHtml);

                $plainText = trim(preg_replace('/\s+/', ' ', $plainText));

                $data['text'] = $plainText;
            }

        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }
}
