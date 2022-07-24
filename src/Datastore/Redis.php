<?php

namespace CrawlerCoinMarketCap\Datastore;
use Exception;
use Predis\Client;

class Redis
{
    static private $instance;

    static private $redis;

    private function __construct()
    {
        try {
            $redis = new Client([
                'host' => 'crawler_redis' // docker container name, app_redis
            ]);
        } catch (Exception $exception) {
            echo 'Not connected';
        }

        self::$redis = $redis;
    }

    static public function get_redis()
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }
        return self::$redis;
    }
}