<?php

use CrawlerCoinMarketCap\Factory;

require __DIR__ . '/vendor/autoload.php'; // Composer's autoloader

header("Content-Type: text/plain");

$crawler = Factory::createCrawlerService();
$alertService = Factory::createAlertService();

$crawler->invoke();
$currentCoins = $crawler->getTokensWithInformation();
if (empty($currentCoins)) {
    $crawler->getClient()->quit();
    die('Nothing to show' . PHP_EOL);
}

$alertService->sendMessage($currentCoins);

echo 'Downloading information about gainers and losers ' . date('H:i:s') . PHP_EOL;
