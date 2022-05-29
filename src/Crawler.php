<?php

namespace CrawlerCoinMarketCap;

use ArrayIterator;
use Exception;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Symfony\Component\Panther\Client as PantherClient;

class Crawler
{
    private PantherClient $client;

    public string $linksForCMC;

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

    /**
     * @return array
     */
    public function getReturnArray(): array
    {
        return $this->returnArray;
    }

    public function __construct()
    {
        $this->client = PantherClientSingleton::getChromeClient();
        $this->returnArray = [];
    }

    public function invoke()
    {
        try {
            $this->client->start();
            $this->client->get('https://coinmarketcap.com/gainers-losers/');
            $this->client->executeScript(self::SCRIPT);
            sleep(2);
            $content = $this->getContent();
            sleep(2);
            $this->assignElementsFromContent($content);
            sleep(2);
            $this->assignDetailInformationToCoin();
            sleep(2);

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
            try {
                $name = $webElement->findElement(WebDriverBy::tagName('a'))
                    ->findElement(WebDriverBy::tagName('p'))->getText();
                $link = $webElement->findElement(WebDriverBy::tagName('a'))
                    ->getAttribute('href');
                $price = $webElement->findElement(WebDriverBy::cssSelector('td:nth-child(3)'))
                    ->getText();
                $percent = (float)$webElement->findElement(WebDriverBy::cssSelector('td:nth-child(4)'))
                    ->getText();
            } catch (Exception $e) {
                echo 'Error when crawl information ' . $e->getMessage() . PHP_EOL;
                continue;
            }

            if ($percent > 20) {
                $this->returnArray[] = new Coin($name, $price, $percent, $link);
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


}