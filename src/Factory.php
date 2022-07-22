<?php

namespace CrawlerCoinMarketCap;

use CrawlerCoinMarketCap\service\AlertService;
use CrawlerCoinMarketCap\service\CrawlerService;


class Factory
{
    public static function createCrawlerService(): CrawlerService
    {
        return new CrawlerService();
    }

    public static function createAlert(): AlertService
    {
        return new AlertService();
    }

}