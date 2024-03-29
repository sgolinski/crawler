<?php

namespace CrawlerCoinMarketCap\Service;

use ArrayIterator;
use CrawlerCoinMarketCap\Entity\Token;
use CrawlerCoinMarketCap\Factory;
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

class Crawler
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

    private array $tokensFromLastCronjob = [];

    private array $tokensWithInformation = [];

    private array $tokensWithoutInformation = [];

    private array $tokensFromCurrentCronjob = [];

    public array $allTokensProcessed;

    private const URL = 'https://coinmarketcap.com/gainers-losers/';

    public function __construct()
    {
        $this->tokensFromLastCronjob = FileReader::readTokensFromLastCronJob();
        $this->allTokensProcessed = FileReader::readTokensAlreadyProcessed();
    }

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
            $this->tokensFromLastCronjob = [];
            FileWriter::writeTokensFromLastCronJob($this->tokensFromCurrentCronjob);
            FileWriter::writeTokensToListTokensAlreadyProcessed($this->allTokensProcessed);

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
                $percent = (float)$webElement->findElement(WebDriverBy::cssSelector('td:nth-child(4)'))
                    ->getText();
                $percent = DropPercent::fromFloat((float)$percent);

                if ($percent->asFloat() < 19.0) {
                    continue;
                }

                $name = $webElement->findElement(WebDriverBy::tagName('a'))
                    ->findElement(WebDriverBy::tagName('p'))->getText();
                $name = Name::fromString($name);

                $fromLastRound = $this->returnTokenIfIsFromLastCronjob($name);

                if ($fromLastRound !== null) {
                    $this->tokensFromCurrentCronjob[] = $fromLastRound;
                    continue;
                }

                $find = $this->getFromRecordedTokens($name);

                if ($find !== null) {
                    $currentTimestamp = time();
                    $find->setDropPercent($percent);
                    $find->setCreated($currentTimestamp);
                    $this->tokensWithInformation[] = $find;
                    $this->tokensFromCurrentCronjob[] = $find;

                } else {
                    $url = $webElement->findElement(WebDriverBy::tagName('a'))
                        ->getAttribute('href');
                    $url = Url::fromString($url);

                    $price = $webElement->findElement(WebDriverBy::cssSelector('td:nth-child(3)'))
                        ->getText();
                    $price = Price::fromFloat((float)$price);

                    $currentTimestamp = time();

                    $address = Address::fromString('');
                    $chain = Chain::fromString('');
                    $this->tokensWithoutInformation[] = Factory::createBscToken($name, $price, $percent, $url, $address, $currentTimestamp, $chain);
                }
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

        foreach ($this->tokensWithoutInformation as $token) {
            try {
                $this->client->refreshCrawler();
                $this->client->get($token->getUrl()->asString());
                $cont = $this->client->getCrawler()
                    ->filter('div.content')
                    ->filter('a.cmc-link')
                    ->getAttribute('href');

                assert($token instanceof Token);
                if (!empty($cont) && str_contains($cont, 'bsc')) {
                    $chain = Chain::fromString('bsc');
                    $address = Address::fromString($cont);
                    $newToken = Factory::createBscToken(
                        $token->getName(), $token->getPrice(),
                        $token->getPercent(),
                        $token->getUrl(),
                        $address,
                        $token->getCreated(),
                        $chain
                    );
                    $this->tokensWithInformation[] = $newToken;
                    $this->tokensFromCurrentCronjob[] = $newToken;
                    $this->allTokensProcessed[] = $newToken;
                }
            } catch (Exception $exception) {
                continue;
            }
        }
        $this->tokensWithoutInformation = [];
        echo 'Finish assigning chain and address ' . date('H:i:s', time()) . PHP_EOL;
    }

    private function getFromRecordedTokens(
        Name $name
    ): ?Token
    {
        foreach ($this->allTokensProcessed as $existedRecord) {
            assert($existedRecord instanceof Token);
            if ($existedRecord->getName()->asString() === $name->asString()) {
                return $existedRecord;
            }
        }
        return null;
    }

    private function returnTokenIfIsFromLastCronjob(
        Name $name,
    ): ?Token
    {

        foreach ($this->tokensFromLastCronjob as $tokenFromLastCronjob) {
            if ($tokenFromLastCronjob->getName()->asString() === $name->asString()) {
                return $tokenFromLastCronjob;
            }
        }
        return null;
    }

    public function getTokensWithInformation(): array
    {
        return $this->tokensWithInformation;
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

    public function resetTokensWithInformation()
    {
        $this->tokensWithInformation = [];
    }

}