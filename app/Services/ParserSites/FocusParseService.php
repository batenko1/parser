<?php

namespace App\Services\ParserSites;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class FocusParseService implements ParserSitesInterface
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
            preg_match('/(\d+)(?:-[^\/]*)?$/', parse_url($link, PHP_URL_PATH), $matches);
            $articleId = $matches[1] ?? null;

            if (!$articleId) {
                return $data;
            }

            $apiUrl = "https://focus.ua/uk/ajax/articles/viewed/{$articleId}";

            $responseViews = Http::withHeaders([
                'Accept' => 'application/json, text/javascript, */*; q=0.01',
                'Content-Type' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36',
                'Referer' => $link,
                'Origin' => 'https://focus.ua',
            ])->post($apiUrl);

            if ($responseViews->successful()) {
                $json = $responseViews->json();
                if (is_array($json) && isset($json['count'])) {
                    $data['views'] = (int) $json['count'];
                } elseif (is_numeric($json)) {
                    $data['views'] = (int) $json;
                }
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

            if (empty($metaTitle) && $crawler->filter('title')->count()) {
                $metaTitle = $crawler->filter('title')->text();
            }

            $data['meta_title'] = trim($metaTitle);
            $data['meta_description'] = trim($metaDescription);

            $textNode = $crawler->filter('.s-content')->first();
            if ($textNode->count()) {
                $articleText = trim(preg_replace('/\s+/', ' ', strip_tags($textNode->html())));
                $data['text'] = $articleText;
            }

        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }
}
