<?php

namespace App\Services\ParserSites;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class UkranewsParseService extends BaseParseService implements ParserSitesInterface
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
            if ($crawler->filter('.b-article__content')->count()) {
                $textNode = $crawler->filter('.b-article__content')->first();
                $text = trim(preg_replace('/\s+/', ' ', strip_tags($textNode->html())));
                $data['text'] = $text;
            }

            // --- VIEWS ---
            if ($crawler->filter('.b-article__view')->count()) {
                $viewsText = $crawler->filter('.b-article__view')->text();
                if (preg_match('/(\d[\d\s]*)/', $viewsText, $m)) {
                    $data['views'] = (int) str_replace(' ', '', $m[1]);
                }
            }

        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }
}
