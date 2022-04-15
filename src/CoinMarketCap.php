<?php

namespace CrawlerCoinMarketCap;

use Exception;
use Maknz\Slack\Client as SlackClient;
use Maknz\Slack\Message;

class CoinMarketCap
{
    private array $currentRound;

    private $lastRoundCoins;

    private SlackClient $slack;

    private const HOOK = 'https://hooks.slack.com/services/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';

    public function __construct()
    {
        $this->slack = new SlackClient(self::HOOK);
        $this->takeCoinsFromLastRound();
    }

    public function invoke($coins): void
    {
        $this->setCurrentCoins($coins);
        if (empty($this->currentRound)) {
            die('Nothing to show');
        }
        $this->currentRound = self::removeDuplicates($this->currentRound, $this->lastRoundCoins);
        $this->checkIfIsBscAndSendMessage();
    }

    public function checkIfIsBscAndSendMessage()
    {
        foreach ($this->currentRound as $coin) {
            assert($coin instanceof Coin);
            if ($coin->mainet == 'bsc') {
                $message = new Message();
                $message->setText($coin->getDescription());
                $this->slack->sendMessage($message);
            }
        }
    }

    private function takeCoinsFromLastRound(): void
    {
        try {
            $this->lastRoundCoins = unserialize(file_get_contents('last_rounded_coins.txt'));
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        if (empty($this->lastRoundCoins)) {
            $this->lastRoundCoins = [];
        }
    }

    public function setCurrentCoins(array $currentCoins)
    {
        $this->currentRound = $currentCoins;
    }

    public static function removeDuplicates($arr1, $arr2)
    {
        $uniqueArray = [];
        $notUnique = false;
        if (!empty($arr2)) {
            foreach ($arr1 as $coin) {
                $notUnique = false;
                foreach ($arr2 as $coin2) {
                    if ($coin->getName() == $coin2->getName()) {
                        $notUnique = true;
                    }
                }
                if (!$notUnique) {
                    $uniqueArray[] = $coin;
                }
            }
            return $uniqueArray;
        } else {
            return $arr1;
        }
    }
}