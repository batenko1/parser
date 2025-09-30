<?php

namespace App\Services\ParserSites;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class UnianParseService implements ParserSitesInterface
{
    public function parse(string $link): int|string
    {
        if (!preg_match('/-(\d+)\.html$/', $link, $matches)) {
            return 'id не найден';
        }

        $newsId = $matches[1];

        $randomCookie = 'cf_clearance='. Str::random(6) .'; another_cookie='. Str::random(6) .';';

        $jsonResponse = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:129.0) Gecko/20100101 Firefox/129.0',
            'Accept' => 'application/json, text/javascript, */*; q=0.01',
            'X-Requested-With' => 'XMLHttpRequest',
            'Referer' => $link,
            'Cookie' => $randomCookie,
        ])
            ->get("https://www.unian.ua/ajax/views/{$newsId}");

        if ($jsonResponse->successful()) {
            return $jsonResponse->json('views') ?? 0;
        }

        return 0;
    }
}
