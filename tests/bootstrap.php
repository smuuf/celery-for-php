<?php

require __DIR__ . '/../vendor/autoload.php';

\Tester\Environment::setup();

class TestEnv {

	private static string $redisUri;

	public static function init(): void {

		$local = !getenv('DOCKER_TESTS');

		if ($local) {
			// Local test env.
			self::$redisUri = '127.0.0.1';
		} else {
			// Inside Docker test env.
			self::$redisUri = 'redis-server';
		}

	}

	public static function getRedisUri(): string {
		return self::$redisUri;
	}

}

TestEnv::init();
