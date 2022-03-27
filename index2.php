<?php
//
//
//use CrawlerCoinMarketCap\Coin;
//use Facebook\WebDriver\Remote\RemoteWebElement;
//use Facebook\WebDriver\WebDriverBy;
//use Maknz\Slack\Client as Slack;
//use Maknz\Slack\Message;
//use Symfony\Component\Panther\Client as PantherClient;
//
//
//require __DIR__ . '/vendor/autoload.php'; // Composer's autoloader
//
//header("Content-Type: text/plain");
//
////$hookUrl = 'https://hooks.slack.com/services/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';
////$slack = new Slack($hookUrl);
//
////$script = <<<EOF
////// get all DIV elements
////var items = document.querySelectorAll('div');
////var clickDiv = false;
////for(const item of items) {
////    // find the first div that contains the text 24h
////    if (item.innerText == "24h") {
////        clickDiv = item;
////        break;
////    }
////}
////// click this div to show up the dropdown
////clickDiv.click();
////
////// now we get the new div
////var dropdown = clickDiv.nextSibling;
////
////// select the first button and click it (1h)
////dropdown.querySelector("button").click();
////EOF;
//
////$returnArray = [];
//
////try {
////    $lastRoundCoins = file_get_contents('last_rounded_coins.txt');
////} catch (Exception $exception) {
////    echo 'File is empty ' . $exception->getMessage() . PHP_EOL;
////}
////try {
////    $client = PantherClient::createChromeClient();
////    $client->start();
////    $client->get('https://coinmarketcap.com/gainers-losers/');
////    $client->executeScript($script);
////    sleep(2);
////
////} catch (Exception $e) {
////
////}
//
////try {
////    $content = $client->getCrawler()
////        ->filter('div.sc-1yw69nc-0.DaVcG.table-wrap > div > div:nth-child(2)')
////        ->filter('table.h7vnx2-2.cZkmip.cmc-table > tbody')
////        ->children()
////        ->getIterator();
////} catch (Exception $exception) {
////    echo $exception->getMessage() . PHP_EOL;
////    die();
////}
//
////foreach ($content as $webElement) {
////
////    assert($webElement instanceof RemoteWebElement);
////    try {
////        $rank = $webElement->findElement(WebDriverBy::tagName('p'))
////            ->getText();
////        $name = $webElement->findElement(WebDriverBy::tagName('a'))
////            ->findElement(WebDriverBy::tagName('p'))->getText();
////        $link = $webElement->findElement(WebDriverBy::tagName('a'))
////            ->getAttribute('href');
////        $price = $webElement->findElement(WebDriverBy::cssSelector('td:nth-child(3)'))
////            ->getText();
////        $percent = (float)$webElement->findElement(WebDriverBy::cssSelector('td:nth-child(4)'))
////            ->getText();
////    } catch (Exception $e) {
////        echo 'Error when crawl information ' . $e->getMessage() . PHP_EOL;
////        continue;
////    }
////    if ($percent > 30.00) {
////        $returnArray[] = new Coin($name, $price, $percent, $link);
////    }
////
////}
////$client->quit();
//
//foreach ($returnArray as $coin) {
//    try {
//        $client->get($coin->getCmcLink());
//        sleep(1);
//    } catch (Exception $e) {
//        echo 'Error when downloading information ' . $e->getMessage() . PHP_EOL;
//        continue;
//    }
//
//    try {
//        $cont = $client->getCrawler()
//            ->filter('div.content')
//            ->filter('a.cmc-link')
//            ->getAttribute('href');
//
//        if (!empty($cont) && str_contains($cont, 'bsc')) {
//            $coin->setMainet('bsc');
//            $coin->setAddress($cont);
//        }
//
//    } catch (Exception $e) {
//        echo 'Error when download and assign address' . $e->getMessage() . PHP_EOL;
//        continue;
//    }
//}
//$client->quit();
//file_put_contents('last_rounded_coins.txt', serialize($returnArray));
//
//if (!empty($lastRoundCoins)) {
//    $returnArray = removeDuplicates($returnArray, unserialize($lastRoundCoins));
//}
//foreach ($returnArray as $coin) {
//
//    assert($coin instanceof Coin);
//    if ($coin->getAddress() != null && $coin->getMainet() == "bsc") {
//        $message = new Message();
//        $message->setText($coin->getDescription());
//        $slack->sendMessage($message);
//    }
//}
//sleep(30);
//echo 'Downloading information about gainers and losers ' . date("F j, Y, g:i a") . PHP_EOL;
//
////function removeDuplicates($arr1, $arr2)
////{
////    $uniqueArray = [];
////    $notUnique = false;
////    if (!empty($arr2)) {
////        foreach ($arr1 as $coin) {
////            $notUnique = false;
////            foreach ($arr2 as $coin2) {
////                if ($coin->getName() == $coin2->getName()) {
////                    $notUnique = true;
////                }
////            }
////            if (!$notUnique) {
////                $uniqueArray[] = $coin;
////            }
////        }
////        return $uniqueArray;
////    } else {
////        return $arr1;
////    }
////}
