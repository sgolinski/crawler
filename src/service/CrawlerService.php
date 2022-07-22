<?php

namespace CrawlerCoinMarketCap\service;

use ArrayIterator;
use CrawlerCoinMarketCap\CmcToken;
use CrawlerCoinMarketCap\Reader\FileReader;
use CrawlerCoinMarketCap\ValueObjects\DropPercent;
use CrawlerCoinMarketCap\ValueObjects\Name;
use CrawlerCoinMarketCap\ValueObjects\Price;
use CrawlerCoinMarketCap\ValueObjects\Url;
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

    private static array $LAST_ROUNDED_COINS;

    private const URL = 'https://coinmarketcap.com/gainers-losers/';

    public function __construct()
    {
        self::$LAST_ROUNDED_COINS = FileReader::read();
        $this->returnArray = [];
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

        } catch (Exception $exception) {
            echo $exception->getMessage() . PHP_EOL;
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


            //Wyciagnij najpierw tylko nazwe;
            //zapisuj baze danych z tokenami ile sie da
            try {
                $name = $webElement->findElement(WebDriverBy::tagName('a'))
                    ->findElement(WebDriverBy::tagName('p'))->getText();
                $name = Name::fromString($name);



                $url = $webElement->findElement(WebDriverBy::tagName('a'))
                    ->getAttribute('href');
                $url = Url::fromString($url);

                $price = $webElement->findElement(WebDriverBy::cssSelector('td:nth-child(3)'))
                    ->getText();
                $price = Price::fromFloat($price);
                $percent = (float)$webElement->findElement(WebDriverBy::cssSelector('td:nth-child(4)'))
                    ->getText();
                $percent = DropPercent::fromFloat($percent);

            } catch (Exception $e) {
                echo 'Error when crawl information ' . $e->getMessage() . PHP_EOL;
                continue;
            }

            if ($percent > 20) {
                $this->returnArray[] = new CmcToken($name, $price, $percent, $url);
            }
        }
    }

    private function assignDetailInformationToCoin()
    {

        foreach ($this->returnArray as $coin) {
            $this->client->refreshCrawler();
            $this->client->get($coin->getCmcLink());
            $cont = $this->client->getCrawler()
                ->filter('div.content')
                ->filter('a.cmc-link')
                ->getAttribute('href');
            assert($coin instanceof Coin);
            if (!empty($cont) && str_contains($cont, 'bsc')) {
                $coin->setMainet('bsc');
                $coin->setAddress($cont);
            }
        }

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


}