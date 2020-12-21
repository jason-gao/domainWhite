<?php


namespace Tests;


use DomainWhiteSdk\Config\Config;
use DomainWhiteSdk\DomainWhiteSdk;

class DomainWhiteTest extends TestCase {


	public function testLoadConfig() {
		$config = Config::load( __DIR__ . "/../domainWhite.conf" );
		$this->assertTrue( is_array( $config ) );
	}

	public static function getConfig() {
		$config = Config::load( __DIR__ . "/../domainWhite.conf" );

		return $config;
	}

	public function testGetToken() {
		DomainWhiteSdk::setConfig( self::getConfig() );
		$token = DomainWhiteSdk::token();
		var_export( "get token: $token" );
		$this->assertEquals( 32, strlen( $token ) );
	}


	//添加域白
	public function testAddDomainWhite() {
		$url     = 'whitelist/add';
		$reqData = [
			'domain' => "lvluoyun.com",
			'idc'    => 2,
			'ip'     => '1.1.1.1',
			'bz'     => "test",
		];
		DomainWhiteSdk::setConfig( self::getConfig() );
		$res = DomainWhiteSdk::addDomainWhite( $url, $reqData );
		var_dump( DomainWhiteSdk::jsonEncodeHold( DomainWhiteSdk::jsonDecodeHold( $res ) ) );
	}


}