<?php

namespace App\Services\ParserSites;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class NewsFinanceParseService implements ParserSitesInterface
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
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'uk-UA,uk;q=0.9,en-US;q=0.8,en;q=0.7',
                'Referer'         => $link,
            ])->get($link);

            if (!$response->successful()) {
                return $data;
            }

            $html = $response->body();
            $crawler = new Crawler($html);

            // Meta title
            $metaTitle = $crawler->filterXPath('//meta[@property="og:title"]');
            if ($metaTitle->count()) {
                $data['meta_title'] = trim($metaTitle->attr('content'));
            } elseif ($crawler->filter('title')->count()) {
                $data['meta_title'] = trim($crawler->filter('title')->text());
            }

            // Meta description
            $metaDescr = $crawler->filterXPath('//meta[@name="description"]');
            if ($metaDescr->count()) {
                $data['meta_description'] = trim($metaDescr->attr('content'));
            }

            $viewsNode = $crawler->filter('.MuiTypography-caption3');
            if ($viewsNode->count()) {
                $viewsText = trim($viewsNode->text());
                $data['views'] = (int) preg_replace('/[^\d]/', '', $viewsText);
            }

            $textNode = $crawler->filter('#main_article_container');
            if ($textNode->count()) {
                $data['text'] = trim(
                    preg_replace('/\s+/', ' ', strip_tags($textNode->html()))
                );
            }

        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }
}
