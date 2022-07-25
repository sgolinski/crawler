<?php

namespace CrawlerCoinMarketCap\Service;

use ArrayIterator;
use CrawlerCoinMarketCap\Entity\Token;
use CrawlerCoinMarketCap\Factory;
use CrawlerCoinMarketCap\Reader\RedisReader;
use CrawlerCoinMarketCap\ValueObjects\Address;
use CrawlerCoinMarketCap\ValueObjects\Chain;
use CrawlerCoinMarketCap\ValueObjects\DropPercent;
use CrawlerCoinMarketCap\ValueObjects\Name;
use CrawlerCoinMarketCap\ValueObjects\Price;
use CrawlerCoinMarketCap\ValueObjects\Url;
use Exception;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Symfony\Component\Panther\Client as PantherClient;

class Crawler
{
    private PantherClient $client;

    private array $currentScrappedTokens = [];

    private const URL = 'https://coinmarketcap.com/gainers-losers/';

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

    public function invoke()
    {
        try {
            $this->startClient();
            $this->client->executeScript(self::SCRIPT);
            sleep(1);
            $this->client->refreshCrawler();
            $content = $this->getContent();
            $this->createTokensFromContent($content);
            $this->assignChainAndAddress();
            $this->getClient()->restart();
        } catch (Exception $exception) {
            echo $exception->getFile() . ' ' . $exception->getLine() . PHP_EOL;
        } finally {
            $this->client->quit();
        }
    }

    public function getContent(): ?ArrayIterator
    {
        echo 'Start getting content ' . date('H:i:s', time()) . PHP_EOL;
        $list = null;
        try {
            $list = $this->client->getCrawler()
                ->filter('div.sc-1yw69nc-0.DaVcG.table-wrap > div > div:nth-child(2)')
                ->filter('table.h7vnx2-2.cZkmip.cmc-table > tbody')
                ->children()
                ->getIterator();

        } catch (Exception $exception) {
            echo $exception->getMessage();
        }
        echo 'Content downloaded ' . date('H:i:s', time()) . PHP_EOL;
        return $list;
    }

    public function createTokensFromContent(
        ArrayIterator $content
    ): void
    {
        echo 'Start creating tokens from content ' . date('H:i:s', time()) . PHP_EOL;

        foreach ($content as $webElement) {
            assert($webElement instanceof RemoteWebElement);

            try {
                $percent = (float)$webElement
                    ->findElement(WebDriverBy::cssSelector('td:nth-child(4)'))
                    ->getText();
                $percent = DropPercent::fromFloat((float)$percent);

                if ($percent->asFloat() < 19.0) {
                    continue;
                }

                $name = $webElement
                    ->findElement(WebDriverBy::tagName('a'))
                    ->findElement(WebDriverBy::tagName('p'))
                    ->getText();
                $name = Name::fromString($name);

                $token = RedisReader::findKey($name);

                if ($token) {
                    continue;
                }

                $url = $webElement
                    ->findElement(WebDriverBy::tagName('a'))
                    ->getAttribute('href');
                $url = Url::fromString($url);

                $price = $webElement
                    ->findElement(WebDriverBy::cssSelector('td:nth-child(3)'))
                    ->getText();
                $price = Price::fromFloat((float)$price);

                $currentTimestamp = time();

                $address = Address::fromString('');
                $chain = Chain::fromString('');

                $this->currentScrappedTokens[] = Factory::createBscToken(
                    $name,
                    $price,
                    $percent,
                    $url,
                    $address,
                    $currentTimestamp,
                    $chain
                );

            } catch (Exception $e) {
                echo 'Error when crawl information ' . $e->getMessage() . PHP_EOL;
                continue;
            }
        }
        echo 'Finish creating tokens from content ' . date('H:i:s', time()) . PHP_EOL;
    }

    private function assignChainAndAddress(): void
    {
        echo 'Start assigning chain and address ' . date('H:i:s', time()) . PHP_EOL;

        foreach ($this->currentScrappedTokens as $token) {
            try {
                assert($token instanceof Token);

                $this->client->refreshCrawler();
                $this->client
                    ->get($token->getUrl()->asString());
                $cont = $this->client->getCrawler()
                    ->filter('div.content')
                    ->filter('a.cmc-link')
                    ->getAttribute('href');

                if (empty($cont) && !str_contains($cont, 'bsc')) {
                    continue;
                }

                $chain = Chain::fromString('bsc');
                $address = Address::fromString($cont);
                $token->setAddress($address);
                $token->setChain($chain);
                $token->setPoocoinAddress($address);

            } catch
            (Exception $exception) {
                continue;
            }
        }

        echo 'Finish assigning chain and address ' . date('H:i:s', time()) . PHP_EOL;
    }

    public function getCurrentScrappedTokens(): array
    {
        return $this->currentScrappedTokens;
    }

    public function getClient(): PantherClient
    {
        return $this->client;
    }

    private function startClient(): void
    {
        echo "Start crawling " . date("F j, Y,  H:i:s") . PHP_EOL;
        $this->client = PantherClient::createChromeClient();
        $this->client->start();
        $this->client->get(self::URL);
    }


}