<?php

use CrawlerCoinMarketCap\Factory;
use CrawlerCoinMarketCap\Writer\RedisWriter;
use CrawlerCoinMarketCap\Datastore\Redis;

require __DIR__ . '/vendor/autoload.php'; // Composer's autoloader

header("Content-Type: text/plain");

$crawler = Factory::createCrawlerService();
$alertService = Factory::createAlertService();

try {
    $crawler->invoke();
} catch (Exception $exception) {
    $crawler->getClient()->restart();
}

$currentCoins = $crawler->getCurrentScrappedTokens();

if (empty($currentCoins)) {
    if (Redis::get_redis()->dbsize()> 300) {
        Redis::get_redis()->flushall();
    }
    die('Nothing to show' . PHP_EOL);
}

$alertService->sendMessage($currentCoins);
echo 'Downloading information about large movers from last hour ' . date('H:i:s') . PHP_EOL;
echo 'Start saving to Redis ' . date('H:i:s') . PHP_EOL;
RedisWriter::writeToRedis($currentCoins);
echo 'Finish saving to Redis ' . date('H:i:s') . PHP_EOL;
$size = Redis::get_redis()->dbsize();

