<?php

namespace CrawlerCoinMarketCap\Reader;

class FileReader implements Reader
{
    public static function readTokensFromLastCronJob(): array
    {
        return unserialize(file_get_contents('/mnt/app/last_rounded_coins.txt'));
    }

    public static function readTokensAlreadyProcessed(): array
    {
        return unserialize(file_get_contents('/mnt/app/aptokens_already_recorded.txt'));
    }
}