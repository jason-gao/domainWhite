<?php

/**
 * DomainWhiteSdk::setConfig(xx);
 * DomainWhiteSdk::addDomainWhite(xx, xx);
 * DomainWhiteSdk::delDomainWhite(xx, xx);
 */

namespace DomainWhiteSdk;


use DomainWhiteSdk\Http\RawRequest;
use DomainWhiteSdk\HttpClients\GuzzleHttpClient;
use DomainWhiteSdk\Logger\MonologLogger;
use DomainWhiteSdk\Cache\MemCachedStore;
use DomainWhiteSdk\Cache\RedisCacheStore;
use DomainWhiteSdk\Cache\CacheStoreInterface;


class DomainWhiteSdk {
	private static $config;
	private static $cache;
	private static $client;
	public static $token;

	public function __construct() {
	}

	public static function setConfig( $config ) {
		self::$config = $config;
	}

	public static function getNormalConfig() {
		return self::$config['normal'];
	}


	public static function setCache( CacheStoreInterface $cache ) {
		self::$cache = $cache;
	}

	public static function getCache() {
		if ( ! self::$cache ) {
			$driver = isset( self::getNormalConfig()['cache_driver'] ) ? self::getNormalConfig()['cache_driver'] : 'memcached';
			if ( $driver == 'memcached' ) {
				MemCachedStore::setConf( self::$config );
				self::$cache = MemCachedStore::getInstance( 'memcache_domain_white' );
			} else if ( $driver == 'redis' ) {
				RedisCacheStore::setConf( self::$config );
				self::$cache = RedisCacheStore::getInstance( 'redis_domain_white' );
			}
		}

		return self::$cache;
	}


	public static function getLogger() {
		$logger = null;
		if ( self::getNormalConfig()['sdk_log_switch'] ) {
			$logger = MonologLogger::getLoggerInstance( __CLASS__, self::getNormalConfig()['sdk_log'] );
		}

		return $logger;
	}

	public static function getClient() {
		if ( ! self::$client ) {
			self::$client = new GuzzleHttpClient( null, self::getLogger() );
		}

		return self::$client;
	}

	public static function token() {
		$url            = self::getNormalConfig()['token_url'];
		$dateline       = time();
		$cacheRes       = self::getCache()->get( 'white_token' );
		$cacheResDecode = self::jsonDecodeHold( $cacheRes );
		if ( empty( $cacheResDecode ) || ( isset( $cacheResDecode['expiry'] ) && $cacheResDecode['expiry'] < ( $dateline - self::getNormalConfig()['token_expiry'] - 100 ) ) ) {
			$body       = self::apiCall( $url, 'post', [
				'uname' => self::getNormalConfig()['uname'],
				'upass' => self::getNormalConfig()['upass']
			], [
				'Content-Type' => 'application/x-www-form-urlencoded'
			] );
			$bodyDecode = self::jsonDecodeHold( $body );
			if ( isset( $bodyDecode['status'] ) && $bodyDecode['status'] == 1 ) {
				$cacheResDecode = $bodyDecode['data'];
				$cacheRes       = self::jsonEncodeHold( $cacheResDecode );
				self::getCache()->set( 'white_token', $cacheRes );
			}
		}
		static::$token = isset( $cacheResDecode['token'] ) ? $cacheResDecode['token'] : '';

		return static::$token;
	}


	public static function getRequestHeaders() {
		$header = array(
			'Authorization' => static::token(),
			'Content-Type'  => 'application/x-www-form-urlencoded'
		);

		return $header;
	}

	public static function apiCall( $url, $method, $body, $headers = [], $timeOut = 20, $options = [] ) {
		$url  = self::getNormalConfig()['base_api_url'] . $url;
		$body = RawRequest::build_query( $body );
		if ( strtolower( $method ) == 'get' ) {
			$url .= $body;
		}
		$rawResponse = self::getClient()->send( $url, $method, $body, $headers, $timeOut, $options );
		$resBody     = $rawResponse->getBody();

		return $resBody;
	}

	public static function addDomainWhite( $url = 'whitelist/add', $data = [] ) {
		$headers = self::getRequestHeaders();
		$res     = self::apiCall( $url, 'post', $data, $headers );

		return $res;
	}


	public static function delDomainWhite( $url = 'whitelist/del', $data = [] ) {
		$headers = self::getRequestHeaders();
		$res     = self::apiCall( $url, 'post', $data, $headers );

		return $res;
	}

	public static function jsonEncodeHold( $data, $isPretty = 0 ) {
		if ( $isPretty ) {
			return json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
		} else {
			return json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		}
	}

	public static function jsonDecodeHold( $data, $assoc = true ) {
		$res = json_decode( $data, $assoc );

		return $res;
	}

}
