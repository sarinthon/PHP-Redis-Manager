<?php

namespace ShuGlobal\RedisManager;

use Predis\Client;

class RedisManager
{
    private static ?Client $client = null;

    private static ?array $parameters = null;
    private static ?array $options = null;

    public static function setup($parameters, $options) {
        self::$parameters = $parameters;
        self::$options = $options;
    }

    private static function getRedisClient() {
        if (isset(self::$client)) {
            return self::$client;
        }

        self::$client = new Client(self::$parameters,self::$options);

        return self::$client;
    }

    // = = = Key-Value

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

    // = = = Array

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

    //  = = = Score

    public static function increseScore(string $cacheName, int $incr, string $member) {
        $client = self::getRedisClient();
        $client->zincrby($cacheName, $incr, $member);
    }

    public static function getScore(string $cacheName, int $start=0, int $end=-1) {
        $client = self::getRedisClient();
        return $client->zrange($cacheName, $start, $end);
    }

    public static function getCacheArray($cacheName, $start = 0, $stop = -1) {
        $client = self::getRedisClient();
        return $client->lrange($cacheName, $start, $stop);
    }

    // = = = JSON

    public static function setJSON(string $cacheName, string $data, string $path="$") {
        $client = self::getRedisClient();
        $client->executeRaw(["JSON.SET", $cacheName, $path, $data]);
    }

    public static function appendJSON(string $cacheName, string $data, string $path="$") {
        $client = self::getRedisClient();
        return $client->executeRaw(["JSON.ARRAPPEND", $cacheName, $path, $data]);
    }

    public static function getJSON(string $cacheName, string $path="$") {
        $client = self::getRedisClient();
        return $client->executeRaw(["JSON.GET", $cacheName, $path]);
    }

    public static function arrayIndexJSON(string $cacheName, string $search, string $path="$") {
        $client = self::getRedisClient();
        return $client->executeRaw(["JSON.ARRINDEX", $cacheName, $path, $search]);
    }

    public static function numIncrbyJSON(string $cacheName, string $number, string $path="$") {
        $client = self::getRedisClient();
        return $client->executeRaw(["JSON.NUMINCRBY", $cacheName, $path, $number]);
    }

    public static function arrpopJSON(string $cacheName, string $data, string $path="$") {
        $client = self::getRedisClient();
        return $client->executeRaw(["JSON.ARRPOP", $cacheName, $path, $data]);
    }

    // = = = Utility

    public static function cacheExist($cacheName) {
        return self::getTTL($cacheName) != -2;
    }

    public static function deleteCache(string $cacheName) {
        $client = self::getRedisClient();
        return $client->executeRaw(["DEL", $cacheName]);
    }

    public static function getTTL($cacheName) {
        $client = self::getRedisClient();
        return $client->ttl($cacheName);
    }

    private static function setExpiration($client, $cacheName, int $second = null, int $timestamp = null) {
        if (isset($second)) {
            $client->expire($cacheName, $second);
        }
        else if (isset($timestamp)){
            $client->expireat($cacheName, $timestamp);
        }
    }
}