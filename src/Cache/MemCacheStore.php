<?php

/**
 * @node_name memcache
 * Desc:
 * Created by PhpStorm.
 * User: jasong
 *  http://php.net/manual/zh/memcache.connect.php
 */

namespace DomainWhiteSdk\Cache;

use DomainWhiteSdk\Exceptions\CacheException;
use Memcache;

class MemCacheStore
{
    private static $instance    = [];
    private static $config = [];

    /**
     * @param $conf
     * @node_name
     * @link
     * @desc
     * $conf = [
     *      'xx' => [
     *          "MEMCACHE_HOST" => "127.0.0.1",
     *          "MEMCACHE_PORT" => '11211',
     *          "MEMCACHE_TIMEOUT" => 1,
     *      ]
     * ];
     * MemcacheService::setConf();
     */
    public static function setConf($conf)
    {
        self::$config = $conf;
    }

    /**
     * @param $key
     * @return mixed
     * @throws CacheException
     * @node_name
     * @link
     * @desc
     */
    public static function getInstance($key)
    {
        if (!isset(self::$instance[$key])) {
            $memcache = new Memcache();
            try {
                $memcache->connect(self::$config[$key]['MEMCACHE_HOST'], self::$config[$key]['MEMCACHE_PORT'], self::$config[$key]['MEMCACHE_TIMEOUT']);
            } catch (\Exception $e) {
                throw new CacheException($e->getMessage(), $e->getCode());
            }

            self::$instance[$key] = $memcache;
        }

        return self::$instance[$key];
    }

}