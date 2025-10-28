<?php

namespace App\Services\ParserSites;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class ItcParseService extends BaseParseService implements ParserSitesInterface
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

            $headers = $this->getRandomHeaders($link);

            $response = Http::withHeaders($headers)->get($link);

            if (!$response->successful()) {
                return $data;
            }

            $html = $response->body();
            $crawler = new Crawler($html);

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

            if ($crawler->filter('.entry-content')->count()) {
                $textNode = $crawler->filter('.entry-content')->first();
                $text = trim(preg_replace('/\s+/', ' ', strip_tags($textNode->html())));
                $data['text'] = $text;
            }

            $path = parse_url($link, PHP_URL_PATH);
            $statsUrl = "https://stats.itc.ua/api/article?url={$path}";

            $statsResponse = Http::withHeaders($headers)->get($statsUrl);


            if ($statsResponse->successful()) {
                $json = $statsResponse->json();
                if (isset($json['data']['views'])) {
                    $data['views'] = (int)$json['data']['views'];
                }
            }

        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }
}
