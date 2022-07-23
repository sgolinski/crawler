<?php

namespace CrawlerCoinMarketCap\Writer;

class FileWriter implements Writer
{

    public static function writeTokensFromLastCronJob(array $makers): void
    {
        file_put_contents('/mnt/app/last_rounded_coins.txt', serialize($makers));
    }

    public static function writeTokensToListTokensAlreadyProcessed(array $makers): void
    {
        file_put_contents('/mnt/app/tokens_already_recorded.txt', serialize($makers));
    }


}