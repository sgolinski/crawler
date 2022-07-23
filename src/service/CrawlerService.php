<?php

namespace CrawlerCoinMarketCap\service;

use ArrayIterator;
use CrawlerCoinMarketCap\CmcToken;
use CrawlerCoinMarketCap\Reader\FileReader;
use CrawlerCoinMarketCap\ValueObjects\Address;
use CrawlerCoinMarketCap\ValueObjects\Chain;
use CrawlerCoinMarketCap\ValueObjects\DropPercent;
use CrawlerCoinMarketCap\ValueObjects\Name;
use CrawlerCoinMarketCap\ValueObjects\Price;
use CrawlerCoinMarketCap\ValueObjects\Url;
use CrawlerCoinMarketCap\Writer\FileWriter;
use Exception;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Symfony\Component\Panther\Client as PantherClient;

class CrawlerService
{
    private PantherClient $client;
    private const SCRIPT = <<<EOF
// get all DIV elements
var items = document.querySelectorAll('div');
var clickDiv = false;
for(const item of items) {
    // find the first div that contains the text 24h
    if (item.innerText == "24h") {
        clickDiv = item;
        break;
    }
}
// click this div to show up the dropdown
clickDiv.click();

// now we get the new div
var dropdown = clickDiv.nextSibling;

// select the first button and click it (1h)
dropdown.querySelector("button").click();
EOF;

    private array $returnArray;

    private static array $lastRoundedCoins;

    private array $tokensWithInformations = [];

    private array $tokensWithoutInformation = [];

    public static array $recorded_coins;

    private const URL = 'https://coinmarketcap.com/gainers-losers/';

    public function __construct()
    {
        self::$lastRoundedCoins =FileReader::read();
        self::$recorded_coins =FileReader::readSearchCoins();
    }

    public function invoke()
    {
        try {
            $this->startClient();
            $this->client->executeScript(self::SCRIPT);
            sleep(1);
            $this->client->refreshCrawler();
            $content = $this->getContent();
            $this->assignElementsFromContent($content);
            $this->assignDetailInformationToCoin();

            $lastRoundedCoins = array_merge(self::$lastRoundedCoins, $this->tokensWithInformations);
            $uniqueLastRoundedCoins = $this->removeOldTokensAndRemoveDuplicates($lastRoundedCoins);
            FileWriter::write($uniqueLastRoundedCoins);
            FileWriter::writeAlreadyShown(self::$recorded_coins);


        } catch (Exception $exception) {
            echo $exception->getFile() . ' ' . $exception->getLine() . PHP_EOL;
        } finally {
            $this->client->quit();
        }
    }

    public function getContent(): ArrayIterator
    {
        return $this->client->getCrawler()
            ->filter('div.sc-1yw69nc-0.DaVcG.table-wrap > div > div:nth-child(2)')
            ->filter('table.h7vnx2-2.cZkmip.cmc-table > tbody')
            ->children()
            ->getIterator();
    }


    public function assignElementsFromContent(ArrayIterator $content)
    {
        foreach ($content as $webElement) {
            assert($webElement instanceof RemoteWebElement);

            try {

                $percent = (float)$webElement->findElement(WebDriverBy::cssSelector('td:nth-child(4)'))
                    ->getText();
                $percent = DropPercent::fromFloat((float)$percent);

                if ($percent->asFloat() < 5) {
                    continue;
                }

                $name = $webElement->findElement(WebDriverBy::tagName('a'))
                    ->findElement(WebDriverBy::tagName('p'))->getText();
                $name = Name::fromString($name);

                $fromLastRound = $this->checkIfTokenIsNotFromLastRound($name);

                if ($fromLastRound) {
                    continue;
                }
                $find = $this->checkIfIsNotRecorded($name);

                if ($find) {
                    $currentTimestamp = time();
                    $find->setDropPercent($percent);
                    $find->setCreated($currentTimestamp);
                    $this->tokensWithInformations[] = $find;
                    continue;

                } else {
                    $url = $webElement->findElement(WebDriverBy::tagName('a'))
                        ->getAttribute('href');
                    $url = Url::fromString($url);

                    $price = $webElement->findElement(WebDriverBy::cssSelector('td:nth-child(3)'))
                        ->getText();
                    $price = Price::fromFloat((float)$price);
                    $percent = (float)$webElement->findElement(WebDriverBy::cssSelector('td:nth-child(4)'))
                        ->getText();
                    $percent = DropPercent::fromFloat($percent);
                    $currentTimestamp = time();

                    $address = Address::fromString('');
                    $chain = Chain::fromString('');
                    $this->tokensWithoutInformation[] = new CmcToken($name, $price, $percent, $url, $address, $currentTimestamp, $chain);
                }
            } catch (Exception $e) {
                echo 'Error when crawl information ' . $e->getMessage() . PHP_EOL;
                continue;
            }
        }
    }

    private function assignDetailInformationToCoin()
    {

        foreach ($this->tokensWithoutInformation as $token) {
            try {
                $this->client->refreshCrawler();
                $this->client->get($token->getUrl()->asString());
                $cont = $this->client->getCrawler()
                    ->filter('div.content')
                    ->filter('a.cmc-link')
                    ->getAttribute('href');

                assert($token instanceof CmcToken);
                if (!empty($cont) && str_contains($cont, 'bsc')) {
                    $chain = Chain::fromString('bsc');
                    $address = Address::fromString($cont);
                    $newToken = new CmcToken($token->getName(), $token->getPrice(), $token->getPercent(), $token->getUrl(), $address, $token->getCreated(), $chain);
                    $this->tokensWithInformations[] = $newToken;
                    self::$recorded_coins[] = $newToken;
                }
            } catch (Exception $exception) {
                continue;
            }
        }
        $this->tokensWithoutInformation = [];
    }

    public function getClient(): PantherClient
    {
        return $this->client;
    }

    /**
     * @return void
     */
    public function startClient(): void
    {
        echo "Start crawling " . date("F j, Y, g:i:s a") . PHP_EOL;
        $this->client = PantherClient::createChromeClient();
        $this->client->start();
        $this->client->get(self::URL);
    }

    private function checkIfIsNotRecorded(Name $name): ?CmcToken
    {
        foreach (self::$recorded_coins as $existedToken) {
            assert($existedToken instanceof CmcToken);
            if ($existedToken->getName()->asString() === $name->asString()) {
                return $existedToken;
            }
        }
        return null;
    }

    private function checkIfTokenIsNotFromLastRound(Name $name): bool
    {
        foreach (self::$lastRoundedCoins as $showedAlreadyToken) {
            assert($showedAlreadyToken instanceof CmcToken);
            if ($showedAlreadyToken->getName()->asString() === $name->asString()) {
                return true;
            }
        }
        return false;
    }

    private function removeOldTokensAndRemoveDuplicates(array $lastRoundedCoins): array
    {
        {
            $uniqueArray = [];
            foreach ($lastRoundedCoins as $token) {
                assert($token instanceof CmcToken);
                if (empty($notUnique)) {
                    $uniqueArray[] = $token;
                }

                foreach ($uniqueArray as $uniqueProve) {
                    if ($token->getName()->asString() === $uniqueProve->getName()->asString()) {
                        if ($token->getCreated() > $uniqueProve->created) {
                            $uniqueProve->setCreated($token->created);
                            continue;
                        }
                    }
                }
                $uniqueArray[] = $token;
            }
            return $uniqueArray;
        }
    }

    /**
     * @return array
     */
    public function getTokensWithInformations(): array
    {
        return $this->tokensWithInformations;
    }



}