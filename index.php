<?php

use CrawlerCoinMarketCap\Coin;
use CrawlerCoinMarketCap\CoinMarketCap;
use CrawlerCoinMarketCap\service\CrawlerService;

require __DIR__ . '/vendor/autoload.php'; // Composer's autoloader

header("Content-Type: text/plain");

$crawler = new CrawlerService();
$cmc = new CoinMarketCap();
$crawler->invoke();
$currentCoins = $crawler->getReturnArray();


if (empty($currentCoins)) {
    $crawler->getClient()->quit();
    die('Nothing to show' . PHP_EOL);
}
if (count($currentCoins) > 0) {
    file_put_contents('last_rounded_coins.txt', serialize($currentCoins));
}
$cmc->invoke($currentCoins);
echo 'Downloading information about gainers and losers ' . date("F j, Y, g:i a") . PHP_EOL;
