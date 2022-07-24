<?php

namespace CrawlerCoinMarketCap\Reader;

use CrawlerCoinMarketCap\Entity\Token;

interface Reader
{
    public static function readTokenByName(string $name): ?Token;
}