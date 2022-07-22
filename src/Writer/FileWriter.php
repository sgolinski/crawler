<?php

namespace CrawlerCoinMarketCap\Writer;

class FileWriter implements Writer
{

    public static function write(array $makers): void
    {
        file_put_contents('last_rounded_coins.txt', serialize($makers));
    }

    public static function writeAlreadyShown(array $makers): void
    {
        file_put_contents('tokens_already_recorded.txt', serialize($makers));
    }
    public static function writeOne(string $alert)
    {
        file_put_contents('tokens_alerts.txt', $alert . PHP_EOL, FILE_APPEND);
    }
}