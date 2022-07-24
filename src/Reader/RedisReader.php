<?php

namespace CrawlerCoinMarketCap\Reader;

use CrawlerCoinMarketCap\Datastore\Redis;
use CrawlerCoinMarketCap\Entity\Token;

class RedisReader implements Reader
{
    public static function readTokenByName(string $name): ?Token
    {
        $token = Redis::get_redis()->get($name);
        if ($token) {
            return unserialize($token);
        }
        return null;
    }
}