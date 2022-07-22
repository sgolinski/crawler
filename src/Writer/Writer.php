<?php

namespace CrawlerCoinMarketCap\Writer;


interface Writer
{
    public static function write(array $makers): void;
}