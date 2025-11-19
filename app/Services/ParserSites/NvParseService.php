<?php

namespace App\Services\ParserSites;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class NvParseService implements ParserSitesInterface
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
                $data['meta_title'] = trim($metaTitle->attr('content') ?? '');
            } elseif ($crawler->filter('title')->count()) {
                $data['meta_title'] = trim($crawler->filter('title')->text());
            }

            // Meta description
            $metaDescr = $crawler->filterXPath('//meta[@name="description"]');
            if ($metaDescr->count()) {
                $data['meta_description'] = trim($metaDescr->attr('content') ?? '');
            }

            $articleId = $this->extractArticleIdFromLink($link);

            if ($articleId) {
                $host = parse_url($link, PHP_URL_HOST); // biz.nv.ua или nv.ua
                $viewsUrl = "https://{$host}/get_article_views/{$articleId}.html";


                $viewsResp = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:131.0) Gecko/20100101 Firefox/131.0',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Connection' => 'keep-alive',
                    'Referer' => $link,
                    'Upgrade-Insecure-Requests' => '1',
                    'DNT' => '1'
                ])->get($viewsUrl);

                if ($viewsResp->successful()) {
                    $views = trim($viewsResp->body());
                    if (is_numeric($views)) {
                        $data['views'] = (int)$views;
                    }
                }

                $contentSelector = "#article_content_replace_{$articleId}";
                $textNode = $crawler->filter($contentSelector);

                if ($textNode->count()) {
                    $rawHtml = $textNode->html();
                    $data['text'] = trim(
                        preg_replace('/\s+/', ' ', strip_tags($rawHtml))
                    );
                }
            }

        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }

    private function extractArticleIdFromLink(string $link): ?int
    {
        if (preg_match('/(\d+)(?:\.html)?$/', $link, $m)) {
            return (int)$m[1];
        }
        return null;
    }
}
