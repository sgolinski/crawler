<?php

namespace CrawlerCoinMarketCap\Writer;


use CrawlerCoinMarketCap\Datastore\Redis;
use CrawlerCoinMarketCap\Entity\Token;

class RedisWriter implements Writer
{
    public static function writeToRedis(array $tokens): void
    {
        foreach ($tokens as $token) {
            assert($token instanceof Token);
            Redis::get_redis()->set($token->getName()->asString(), serialize($token));
        }
        Redis::get_redis()->save();

    }
}