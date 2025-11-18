<?php

namespace App\Services\ParserSites;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class DonPatriotParseService implements ParserSitesInterface
{
    public function parse(string $link): array
    {
        $data = [
            'meta_title' => '',
            'meta_description' => '',
            'text' => '',
            'views' => 0,
        ];

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
        ])->get($link);

        if (!$response->successful()) {
            return $data;
        }

        $html = $response->body();
        $crawler = new Crawler($html);

        $data['meta_title'] = $crawler->filter('title')->count()
            ? trim($crawler->filter('title')->text())
            : '';

        $metaDescNode = $crawler->filter('meta[name="description"]');

        $data['meta_description'] = $metaDescNode->count()
            ? trim($metaDescNode->attr('content'))
            : '';

        $textNode = $crawler->filter('.entry-content');

        if ($textNode->count()) {
            $textNode->filter('script, style, noscript')->each(fn($n) => $n->getNode(0)->parentNode->removeChild($n->getNode(0)));

            $data['text'] = trim($textNode->html());
        }

        $viewsNode = $crawler->filter('.ex-pda-blocks-views.wp-block-itcode-views-block');

        if ($viewsNode->count()) {
            $viewsText = trim($viewsNode->text());

            $clean = preg_replace('/\D+/', '', $viewsText);

            if ($clean !== '') {
                $data['views'] = (int)$clean;
            }
        }

        return $data;
    }
}
