<?php

namespace App\Services\ParserSites;

class RbcParseService implements ParserSitesInterface
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
