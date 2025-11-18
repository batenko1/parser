<?php

namespace App\Services\ParserSites;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class EpravdaParseService implements ParserSitesInterface
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
                $data['meta_title'] = $metaTitle->attr('content') ?? '';
            } elseif ($crawler->filter('title')->count()) {
                $data['meta_title'] = trim($crawler->filter('title')->text());
            }

            // Meta description
            $metaDescr = $crawler->filterXPath('//meta[@name="description"]');
            if ($metaDescr->count()) {
                $data['meta_description'] = $metaDescr->attr('content') ?? '';
            }

            $textNode = $crawler->filter('.post_news_text');
            if ($textNode->count()) {
                $data['text'] = trim(
                    preg_replace('/\s+/', ' ', strip_tags($textNode->html()))
                );
            }

            $articleId = $this->extractArticleIdFromLink($link);

            if ($articleId) {
                $viewsUrl = "https://epravda.com.ua/article/{$articleId}/count-view.html";

                $viewsResp = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0',
                    'Accept' => 'text/plain, */*',
                    'Referer' => $link,
                ])->get($viewsUrl);

                if ($viewsResp->successful()) {
                    $views = trim($viewsResp->body());

                    if (is_numeric($views)) {
                        $data['views'] = (int) $views;
                    }
                }
            }

        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }

    private function extractArticleIdFromLink(string $link): ?int
    {
        if (preg_match('/(\d+)(?:\/)?$/', $link, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
