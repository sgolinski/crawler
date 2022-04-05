<?php

use CrawlerCoinMarketCap\Coin;
use CrawlerCoinMarketCap\CoinMarketCap;
use CrawlerCoinMarketCap\Crawler;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Maknz\Slack\Client as Slack;
use Maknz\Slack\Message;
use Symfony\Component\Panther\Client as PantherClient;

require __DIR__ . '/vendor/autoload.php'; // Composer's autoloader

header("Content-Type: text/plain");

$crawler = new Crawler();
$cmc = new CoinMarketCap();
$crawler->invoke();
$currentCoins = $crawler->getReturnArray();

file_put_contents('coins_from_cmc.txt', $crawler->linksForCMC, FILE_APPEND);

if (empty($currentCoins)) {
    $crawler->getClient()->quit();
    die('Nothing to show' . PHP_EOL);
}
if (count($currentCoins) > 0) {
    file_put_contents('last_rounded_coins.txt', serialize($currentCoins));
}
$cmc->invoke($currentCoins);
echo 'Downloading information about gainers and losers ' . date("F j, Y, g:i a") . PHP_EOL;

$cmc->linksForAlerts = array_unique($cmc->linksForAlerts);
$count = count(explode("\n", file_get_contents('coins_from_cmc.txt')));

if ($count >= 200) {
    $cmc->sendAttachment(file_get_contents('coins_from_cmc.txt'));
    unlink('coins_from_cmc.txt');
}
sleep(30);