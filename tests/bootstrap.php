<?php

use Predis\Client as PredisClient;

use Smuuf\CeleryForPhp\Celery;
use Smuuf\CeleryForPhp\Config;
use Smuuf\CeleryForPhp\Brokers\RedisBroker;
use Smuuf\CeleryForPhp\Drivers\PredisDriver;
use Smuuf\CeleryForPhp\Backends\RedisBackend;
use Smuuf\CeleryForPhp\Serializers\JsonSerializer;

require __DIR__ . '/../vendor/autoload.php';

\Tester\Environment::setup();

class CeleryFactory {

	public static function getCelery(): Celery {

		$envConfig = self::readEnv();

		$config = new Config(
			taskMessageProtocolVersion: $envConfig['task_message_protocol_version'],
			taskSerializer: match ($envConfig['serializer']) {
				'json' => new JsonSerializer(),
			},
		);

		$predis = new PredisClient(self::getPredisConnectionConfig());
		$redisDriver = new PredisDriver($predis);
		$broker = new RedisBroker($redisDriver);
		$backend = new RedisBackend($redisDriver);

		return new Celery($broker, $backend, $config);

	}

	public static function getPredisConnectionConfig(): array {
		return [
			'host' => '[::1]', // IPv6 localhost.
			'port' => 40001,
		];
	}

	/**
	 * @return array{
	 *     serializer: string,
	 *     message_protocol: int,
	 * }
	 */
	private static function readEnv(): array {

		return [
			'serializer' => getenv('CELERYFORPHP_TASK_SERIALIZER') ?: '',
			'task_message_protocol_version' => (int) getenv('CELERYFORPHP_TASK_MESSAGE_PROTOCOL_VERSION'),
		];

	}

}
