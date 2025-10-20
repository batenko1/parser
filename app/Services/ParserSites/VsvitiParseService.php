<?php

namespace App\Services\ParserSites;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class VsvitiParseService implements ParserSitesInterface
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
            // Извлекаем ID статьи из ссылки
            preg_match('/(\d+)\/?$/', parse_url($link, PHP_URL_PATH), $matches);
            $articleId = $matches[1] ?? null;

            $response = Http::withHeaders([
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'uk-UA,uk;q=0.9,en-US;q=0.8,en;q=0.7',
                'Referer'         => 'https://google.com',
            ])->get($link);

            if (!$response->successful()) {
                return $data;
            }

            $crawler = new Crawler($response->body());

            /** ---------- META ---------- **/
            $metaTitleNode = $crawler->filterXPath('//meta[@property="og:title"]');
            $metaDescriptionNode = $crawler->filterXPath('//meta[@name="description"]');

            $metaTitle = $metaTitleNode->count() ? $metaTitleNode->attr('content') : '';
            $metaDescription = $metaDescriptionNode->count() ? $metaDescriptionNode->attr('content') : '';

            if (empty($metaTitle) && $crawler->filter('title')->count()) {
                $metaTitle = $crawler->filter('title')->text();
            }

            $data['meta_title'] = trim($metaTitle);
            $data['meta_description'] = trim($metaDescription);

            /** ---------- ПРОСМОТРЫ ---------- **/
            $viewsNode = null;

            if ($articleId) {
                $viewsNode = $crawler->filter(".td-post-views .td-nr-views-{$articleId}");
            }

            if (!$viewsNode || !$viewsNode->count()) {
                $viewsNode = $crawler->filter('.td-post-views span');
            }

            if ($viewsNode->count()) {
                $viewsText = trim($viewsNode->text());
                $data['views'] = $this->normalizeViews($viewsText);
            }

            /** ---------- ТЕКСТ ---------- **/
            $textNode = $crawler->filter('.td-post-content');
            if ($textNode->count()) {
                $articleText = trim(preg_replace('/\s+/', ' ', strip_tags($textNode->html())));
                $data['text'] = $articleText;
            }

        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }

    /**
     * Универсальный метод нормализации просмотров
     */
    private function normalizeViews(string $viewsText): int
    {
        $viewsText = mb_strtolower(trim($viewsText));
        $viewsText = str_replace([' ', ','], ['', '.'], $viewsText);

        if (preg_match('/([\d.]+)\s*(k|т|тис|тыс)/u', $viewsText, $matches)) {
            return (int) round(((float)$matches[1]) * 1000);
        }

        if (preg_match('/([\d.]+)\s*(m|млн|м)/u', $viewsText, $matches)) {
            return (int) round(((float)$matches[1]) * 1_000_000);
        }

        return (int) preg_replace('/[^\d]/', '', $viewsText);
    }
}
