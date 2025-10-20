<?php

namespace App\Services\ParserSites;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class CensorParseService implements ParserSitesInterface
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
            $html = $this->fetchWithFallbacks($link);

            if ($html === null) {
                return $data; // так и не достали HTML
            }

            $crawler = new Crawler($html);

            // title/description
            $metaTitle = $crawler->filterXPath('//meta[@property="og:title"]')->count()
                ? $crawler->filterXPath('//meta[@property="og:title"]')->attr('content')
                : ($crawler->filter('title')->count() ? $crawler->filter('title')->text() : '');

            $metaDescription = $crawler->filterXPath('//meta[@name="description"]')->count()
                ? $crawler->filterXPath('//meta[@name="description"]')->attr('content')
                : '';

            $data['meta_title'] = trim($metaTitle ?? '');
            $data['meta_description'] = trim($metaDescription ?? '');

            // просмотры — у Цензора бывает разметка разных типов
            $viewsNode = $crawler->filter('.main-items-text__count, .news__views, [class*="views"]')->first();
            if ($viewsNode->count()) {
                $data['views'] = (int) preg_replace('/[^\d]/', '', trim($viewsNode->text()));
            }

            // тело статьи (универсальные селекторы, включая AMP)
            $textHtml = null;
            foreach ([
                         '.news-text',
                         'article [itemprop="articleBody"]',
                         'article',
                         '.article__text',
                         '.text',
                         '.post__text',
                     ] as $selector) {
                if ($crawler->filter($selector)->count()) {
                    $textHtml = $crawler->filter($selector)->first()->html();
                    break;
                }
            }

            if ($textHtml) {
                $clean = trim(preg_replace('/\s+/', ' ', strip_tags($textHtml)));
                $data['text'] = $clean;
            }

        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }

    private function fetchWithFallbacks(string $url): ?string
    {
        // 1) прямой запрос
        $html = $this->tryRequest($url);
        if ($html !== null) return $html;

        // 2) AMP-версия (часто проходит без CF)
        $ampUrl = $this->toAmpUrl($url);
        if ($ampUrl && $ampUrl !== $url) {
            $html = $this->tryRequest($ampUrl);
            if ($html !== null) return $html;
        }

        // 3) Scraper API (если задан ключ)
        $apiKey = env('SCRAPER_API_KEY');
        if ($apiKey) {
            $res = Http::timeout(30)->get('http://api.scraperapi.com', [
                'api_key' => $apiKey,
                'url'     => $url,
                'render'  => 'false',
            ]);
            if ($res->ok() && $this->looksLikeHtml($res->body())) {
                return $res->body();
            }
        }

        // 4) Системный прокси (HTTP/SOCKS5), если указан в env
        $proxy = env('SCRAPER_PROXY'); // напр. http://user:pass@host:port или socks5://host:port
        if ($proxy) {
            $res = Http::withOptions(['proxy' => $proxy])
                ->withHeaders($this->browserHeaders())
                ->timeout(30)
                ->get($url);
            if ($res->ok() && $this->looksLikeHtml($res->body())) {
                return $res->body();
            }
        }

        return null;
    }

    private function tryRequest(string $url): ?string
    {
        $res = Http::withHeaders($this->browserHeaders())
            ->timeout(30)
            ->get($url);

        // Cloudflare часто дает 403/503 на челлендже
        if ($res->ok() && $this->looksLikeHtml($res->body())) {
            return $res->body();
        }
        return null;
    }

    private function browserHeaders(): array
    {
        // Полнее «как браузер». Эти client-hints не гарантируют проход,
        // но помогают снизить вероятность бана на слабых правилах.
        return [
            'User-Agent'              => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept'                  => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language'         => 'uk-UA,uk;q=0.9,en-US;q=0.8,en;q=0.7',
            'Referer'                 => 'https://google.com',
            // Доп. заголовки, которые иногда ждут (см. accept-ch/critical-ch)
            'Sec-CH-UA'               => '"Chromium";v="120", "Not.A/Brand";v="24", "Google Chrome";v="120"',
            'Sec-CH-UA-Platform'      => '"Windows"',
            'Sec-CH-UA-Mobile'        => '?0',
            'Upgrade-Insecure-Requests'=> '1',
            'DNT'                     => '1',
        ];
    }

    private function toAmpUrl(string $url): ?string
    {
        // https://censor.net/ua/photonews/...  -> https://amp.censor.net/ua/photonews/...
        $parsed = parse_url($url);
        if (!isset($parsed['host']) || stripos($parsed['host'], 'censor.net') === false) {
            return null;
        }
        $scheme = $parsed['scheme'] ?? 'https';
        $path   = $parsed['path'] ?? '/';
        return "{$scheme}://amp.censor.net{$path}";
    }

    private function looksLikeHtml(string $body): bool
    {
        return str_contains($body, '<html') || str_contains($body, '<!DOCTYPE html');
    }
}
