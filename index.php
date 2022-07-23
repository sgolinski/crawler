<?php

use CrawlerCoinMarketCap\CmcToken;

use CrawlerCoinMarketCap\service\AlertService;
use CrawlerCoinMarketCap\service\CrawlerService;

require __DIR__ . '/vendor/autoload.php'; // Composer's autoloader

header("Content-Type: text/plain");

$crawler = new CrawlerService();
$alertService = new AlertService();


$crawler->invoke();
$currentCoins = $crawler->getTokensWithInformations();
if (empty($currentCoins)) {
    $crawler->getClient()->quit();
    die('Nothing to show' . PHP_EOL);
}

$alertService->sendMessage($currentCoins);

echo 'Downloading information about gainers and losers ' . date("F j, Y, g:i a") . PHP_EOL;
