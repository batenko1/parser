<?php

namespace App\Services\ParserSites;

interface ParserSitesInterface
{
    public function parse(string $link): array;
}
