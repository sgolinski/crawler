<?php

namespace CrawlerCoinMarketCap\Service;

use CrawlerCoinMarketCap\Entity\BscToken;
use CrawlerCoinMarketCap\Entity\Token;
use CrawlerCoinMarketCap\Factory;
use Maknz\Slack\Client as SlackClient;

class Alert
{
    private SlackClient $slack;

    private const HOOK = 'https://hooks.slack.com/services/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';

    public function __construct()
    {
        $this->slack = Factory::createSlackClient(self::HOOK);
    }

    public function sendMessage(
        array $currentRound
    ): void
    {
        foreach ($currentRound as $coin) {
            assert($coin instanceof Token);
            if ($coin->chain !== null) {
                $message = Factory::createSlackMessage()->setText($coin->alert());
                $this->slack->sendMessage($message);
            }
        }
    }
}