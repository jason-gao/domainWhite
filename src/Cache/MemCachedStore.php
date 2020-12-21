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
use Memcached;
use Exception;

class MemCachedStore implements CacheStoreInterface {

	private static $cacheInstance;
	private static $config = [];
	private static $instance = [];


	public static function setConf( $conf ) {
		self::$config = $conf;
	}

	public static function getInstance( $key ) {
		if ( ! isset( self::$config[ $key ] ) ) {
			throw new CacheException( "memcached $key conf not exist" );
		}
		if ( ! isset( self::$instance[ $key ] ) ) {
			self::$cacheInstance = new Memcached();
			try {
				self::$cacheInstance->addServer( self::$config[ $key ]['MEMCACHE_HOST'], self::$config[ $key ]['MEMCACHE_PORT'], self::$config[ $key ]['MEMCACHE_WEIGHT'] );
			} catch ( Exception $e ) {
				throw new CacheException( $e->getMessage(), $e->getCode() );
			}

			self::$instance[ $key ] = new self();
		}

		return self::$instance[ $key ];
	}

	// https://www.php.net/manual/zh/memcached.expiration.php
	public function set( $key, $value, $expire = 0 ) {
		self::$cacheInstance->set( $key, $value, $expire );
	}


	public function get( $key ) {
		return self::$cacheInstance->get( $key );
	}

}