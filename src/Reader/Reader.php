<?php

namespace CrawlerCoinMarketCap\Reader;

use CrawlerCoinMarketCap\Entity\Token;
use CrawlerCoinMarketCap\ValueObjects\Name;

interface Reader
{
    public static function readTokenByName(string $name): ?Token;

    public static function findKey(Name $name): bool;
}