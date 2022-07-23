<?php

namespace CrawlerCoinMarketCap\Reader;

interface Reader
{
    public static function readTokensFromLastCronJob(): array;

    public static function readTokensAlreadyProcessed(): array;
}