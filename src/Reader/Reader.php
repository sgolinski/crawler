<?php

namespace CrawlerCoinMarketCap\Reader;

interface Reader
{
    public static function read(): array;
}