<?php

namespace App\Services\ParserSites;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class ZaxidParseService implements ParserSitesInterface
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
            $path = parse_url($link, PHP_URL_PATH) ?: $link;

            if (!preg_match('/n(\d+)/', $path, $matches)) {
                return $data;
            }

            $id = $matches[1];
            $counterUrl = "https://zaxid.net/counter/{$id}";

            $responseCounter = Http::withHeaders([
                'Accept' => 'application/json, text/javascript, */*; q=0.01',
                'Referer' => $link,
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36',
            ])->get($counterUrl);

            if ($responseCounter->successful()) {
                $json = $responseCounter->json();
                if (isset($json['value'])) {
                    $data['views'] = (int)$json['value'];
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

            $articleNode = $crawler->filter('#newsSummary')->first();
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
