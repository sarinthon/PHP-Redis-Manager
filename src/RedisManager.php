<?php

namespace ShuGlobal\RedisManager;

use Predis\Client;

class RedisManager
{
    private static Client|null $client = null;

    private static function getRedisClient() {
        if (isset(self::$client)) {
            return self::$client;
        }

        self::$client = new Client([
            'host'   => env("REDIS_HOST"),
            'port'   => env("REDIS_PORT"),
            'password' => env("REDIS_PASSWORD")
        ]);

        return self::$client;
    }

    public static function getCache($cacheName) {
        $client = self::getRedisClient();
        return json_decode($client->get($cacheName));
    }

    public static function setCache($cacheName, $data, int $second = null, int $timestamp = null) {
        $json = json_encode($data);

        $client = self::getRedisClient();
        $client->set($cacheName, $json);

        self::setExpiration($client, $cacheName, $second, $timestamp);
    }

    public static function pushCache($cacheName, Array $data, int $second = null, int $timestamp = null) {
        if (count($data) == 0) {return;}

        $client = self::getRedisClient();
        $client->lpush($cacheName, $data);

        self::setExpiration($client, $cacheName, $second, $timestamp);
    }

    public static function popCache($cacheName) {
        $client = self::getRedisClient();
        return $client->lpop($cacheName);
    }

    public static function getCacheArray($cacheName, $start = 0, $stop = -1) {
        $client = self::getRedisClient();
        return $client->lrange($cacheName, $start, $stop);
    }

    public static function cacheExist($cacheName) {
        return self::getTTL($cacheName) != -2;
    }

    public static function getTTL($cacheName) {
        $client = self::getRedisClient();
        return $client->ttl($cacheName);
    }

    // Utility

    private static function setExpiration($client, $cacheName, int $second = null, int $timestamp = null) {
        if (isset($second)) {
            $client->expire($cacheName, $second);
        }
        else if (isset($timestamp)){
            $client->expireat($cacheName, $timestamp);
        }
    }
}