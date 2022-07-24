<?php

namespace CrawlerCoinMarketCap\Writer;

interface Writer
{
    public static function writeToRedis(array $tokens): void;
}