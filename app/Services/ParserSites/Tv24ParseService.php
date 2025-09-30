<?php

namespace App\Services\ParserSites;

use Illuminate\Support\Facades\Http;

class Tv24ParseService implements ParserSitesInterface
{
    /**
     * Parse counter value from a 24tv.ua article link.
     *
     * Example link:
     *  https://24tv.ua/agro24/vrozhay-kartopli-nimechchini-naybilshiy-2000-roku_n2921985
     *
     * Steps:
     *  - extract id (2921985)
     *  - GET https://counter24.luxnet.ua/counter/{id}
     *  - return integer value or error string
     *
     * @param string $link
     * @return int|string
     */
    public function parse(string $link): int|string
    {
        try {
            // 1) извлекаем path и ищем "n{digits}" в конце
            $path = parse_url($link, PHP_URL_PATH) ?: $link;

            // пытаемся найти "n12345" либо "_n12345" или "-n12345" в конце
            if (!preg_match('/n(\d+)(?:\.html)?(?:\/?)*$/', $path, $m)) {
                return 'id не найден в ссылке';
            }

            $id = $m[1];

            // 2) делаем запрос к counter24
            $url = "https://counter24.luxnet.ua/counter/{$id}";

            $response = Http::withHeaders([
                'Accept' => 'application/json, text/javascript, */*; q=0.01',
                'Referer' => $link,
                'User-Agent' => 'Mozilla/5.0 (compatible; parser/1.0; +https://example.com)'
            ])->get($url);

            if (! $response->successful()) {
                return 0;
            }

            $payload = $response->json();

            if (is_array($payload) && array_key_exists('value', $payload)) {
                return (int) $payload['value'];
            }

            return 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
