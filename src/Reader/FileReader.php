<?php

namespace CrawlerCoinMarketCap\Reader;

class FileReader implements Reader
{
    public static function readTokensFromLastCronJob(): array
    {
        return unserialize(file_get_contents('last_rounded_coins.txt'));
    }

    public static function readTokensAlreadyProcessed(): array
    {
        return unserialize(file_get_contents('tokens_already_recorded.txt'));
    }
}