<?php

namespace CrawlerCoinMarketCap;

use CrawlerCoinMarketCap\Entity\BscToken;
use CrawlerCoinMarketCap\Service\Alert;
use CrawlerCoinMarketCap\Service\Crawler;
use CrawlerCoinMarketCap\ValueObjects\Address;
use CrawlerCoinMarketCap\ValueObjects\Chain;
use CrawlerCoinMarketCap\ValueObjects\DropPercent;
use CrawlerCoinMarketCap\ValueObjects\Name;
use CrawlerCoinMarketCap\ValueObjects\Price;
use CrawlerCoinMarketCap\ValueObjects\Url;
use Maknz\Slack\Client as SlackClient;
use Maknz\Slack\Message;

class Factory
{
    public static function createCrawlerService(): Crawler
    {
        return new Crawler();
    }

    public static function createAlertService(): Alert
    {
        return new Alert();
    }

    public static function createBscToken(
        Name        $name,
        Price       $price,
        DropPercent $percent,
        Url         $url,
        Address     $address,
        int         $created,
        Chain       $chain,
        bool        $processed
    ): BscToken
    {
        return new BscToken($name, $price, $percent, $url, $address, $created, $chain,$processed);
    }

    public static function createSlackClient(string $hook): SlackClient
    {
        return new SlackClient($hook);
    }

    public static function createSlackMessage(): Message
    {
        return new Message();
    }
}