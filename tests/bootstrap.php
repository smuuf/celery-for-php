<?php

require __DIR__ . '/../vendor/autoload.php';

\Tester\Environment::setup();

class TestEnv {

	private static string $redisUri;

	public static function init(): void {
		self::$redisUri = '127.0.0.1';
	}

	public static function getRedisUri(): string {
		return self::$redisUri;
	}

}

TestEnv::init();
