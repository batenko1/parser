<?php

namespace App\Services\ParserSites;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class ObozrevatelParseService implements ParserSitesInterface
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
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'uk-UA,uk;q=0.9,en-US;q=0.8,en;q=0.7',
                'Referer'         => $link,
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

            $viewsNode = $crawler->filter('.reaction_value')->first();
            if ($viewsNode->count()) {
                $viewsText = trim($viewsNode->text());

                $viewsText = mb_strtolower($viewsText);
                $viewsText = str_replace([' ', ','], ['', '.'], $viewsText);

                if (preg_match('/([\d.]+)\s*(т|тис|k)/u', $viewsText, $matches)) {
                    $data['views'] = (int) round(((float)$matches[1]) * 1000);
                } else {
                    $data['views'] = (int) preg_replace('/[^\d]/', '', $viewsText);
                }
            }

        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }
}
