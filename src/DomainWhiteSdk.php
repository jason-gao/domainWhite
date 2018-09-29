<?php

/**
 * DomainWhiteSdk::setConfig(xx);
 * DomainWhiteSdk::addDomainWhite(xx, xx);
 * DomainWhiteSdk::delDomainWhite(xx, xx);
 */

namespace DomainWhiteSdk;


use DomainWhiteSdk\Cache\MemCacheStore;
use DomainWhiteSdk\Http\RawRequest;
use DomainWhiteSdk\HttpClients\GuzzleHttpClient;
use DomainWhiteSdk\Logger\MonologLogger;

class DomainWhiteSdk
{
    private static $config;
    private static $cache;
    private static $client;
    public static  $token;

    public function __construct()
    {
    }

    public static function setConfig($config)
    {
        self::$config = $config;
    }

    public static function getNormalConfig()
    {
        return self::$config['normal'];
    }

    public static function getCache()
    {
        if (!self::$cache) {
            MemCacheStore::setConf(self::$config);
            self::$cache = MemCacheStore::getInstance(self::getNormalConfig()['memcache_key']);
        }

        return self::$cache;
    }

    public static function getLogger()
    {
        $logger = null;
        if (self::getNormalConfig()['sdk_log_switch']) {
            $logger = MonologLogger::getLoggerInstance(__CLASS__, self::getNormalConfig()['sdk_log']);
        }

        return $logger;
    }

    public static function getClient()
    {
        if (!self::$client) {
            self::$client = new GuzzleHttpClient(null, self::getLogger());
        }

        return self::$client;
    }

    public static function token()
    {
        $url      = self::getNormalConfig()['token_url'];
        $dateline = time();
        $res      = self::getCache()->get('white_token');
        if (empty($res) || (isset($res['expiry']) && $res['expiry'] < ($dateline - self::getNormalConfig()['expiry'] - 100))) {
            $body = self::apiCall($url, 'post', [
                'uname' => self::getNormalConfig()['uname'],
                'upass' => self::getNormalConfig()['upass']
            ], [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]);
            $body = json_decode($body, true);
            if (isset($body['status']) && $body['status'] == 1) {
                $res = $body['data'];
                self::getCache()->set('white_token', $res);
            }
        }
        static::$token = isset($res['token']) ? $res['token'] : '';
        return static::$token;
    }


    public static function getRequestHeaders()
    {
        $header = array(
            'Authorization' => static::token(),
            'Content-Type'  => 'application/x-www-form-urlencoded'
        );

        return $header;
    }

    public static function apiCall($url, $method, $body, $headers = [], $timeOut = 20, $options = [])
    {
        $url  = self::getNormalConfig()['base_api_url'] . $url;
        $body = RawRequest::build_query($body);
        if (strtolower($method) == 'get') {
            $url .= $body;
        }
        $rawResponse = self::getClient()->send($url, $method, $body, $headers, $timeOut, $options);
        $resBody     = $rawResponse->getBody();

        return $resBody;
    }

    public static function addDomainWhite($url = 'whitelist/add', $data = [])
    {
        $headers = self::getRequestHeaders();
        $res     = self::apiCall($url, 'post', $data, $headers);

        return $res;
    }


    public static function delDomainWhite($url = 'whitelist/del', $data = [])
    {
        $headers = self::getRequestHeaders();
        $res     = self::apiCall($url, 'post', $data, $headers);

        return $res;
    }

}
