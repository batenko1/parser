<?php

namespace App\Services\ParserSites;

class RadiotrekParseService implements ParserSitesInterface
{
    public function parse(string $link): array
    {
        return [
            'meta_title' => '',
            'meta_description' => '',
            'text' => '',
            'views' => 0,
        ];
    }
}
