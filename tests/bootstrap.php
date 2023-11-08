<?php

declare(strict_types=1);

use Predis\Client as PredisClient;
use PhpAmqpLib\Connection\AMQPStreamConnection;

use Smuuf\CeleryForPhp\Celery;
use Smuuf\CeleryForPhp\Config;
use Smuuf\CeleryForPhp\Brokers\RedisBroker;
use Smuuf\CeleryForPhp\Drivers\PredisRedisDriver;
use Smuuf\CeleryForPhp\Backends\RedisBackend;
use Smuuf\CeleryForPhp\Brokers\AmqpBroker;
use Smuuf\CeleryForPhp\Drivers\PhpAmqpLibAmqpDriver;
use Smuuf\CeleryForPhp\Serializers\JsonSerializer;

require __DIR__ . '/../vendor/autoload.php';

\Tester\Environment::setup();

class TestCeleryFactory {

	/**
	 * @param array{
	 *     broker_driver: string,
	 *     backend_driver: string,
	 *     task_serializer: string,
	 *     task_message_protocol: int,
	 * }
	 */
	public static function getCelery(array $testArgs): Celery {

		[
			'broker_driver' => $brokerDriver,
			'backend_driver' => $backendDriver,
			'task_serializer' => $taskSerializer,
			'task_message_protocol_version' => $taskMessageProtocolVersion,
		] = $testArgs;

		$config = new Config(
			taskMessageProtocolVersion: $taskMessageProtocolVersion,
			taskSerializer: match ($taskSerializer) {
				'json' => new JsonSerializer(),
			},
		);

		$broker = match ($brokerDriver) {
			'PhpAmqpLibAmqpDriver' => new AmqpBroker(self::getPhpAmqpLibAmqpDriver()),
			'PredisRedisDriver' => new RedisBroker(self::getPredisRedisDriver()),
		};

		$backend = match ($backendDriver) {
			'PredisRedisDriver' => new RedisBackend(self::getPredisRedisDriver()),
		};

		return new Celery($broker, $backend, $config);

	}

	/**
	 * Because we have tests of many combinations of brokers vs result backends
	 * running simultaneously, we need to namespace our test tasks a bit.
	 *
	 * Consider, for example, testing "Redis broker + Redis result backend" as
	 * case A and at the same time having a different test testing "Redis broker
	 * + AMQP result backend" as case B. If a test in case A would post a Celery
	 * task into Redis, but then Celery running for case B (which uses the same
	 * broker) would get to the task first, then case A wouldn't receive the
	 * expected result in case A's redis result backend, because case B's Celery
	 * would execute the task and then store its result in case B's AMQP result
	 * backend.
	 *
	 * This name has to be constructed the same way the Celerys created and
	 * running during tests are constructing it.
	 *
	 * @see tests/infra/celery-app/spawner.py
	 */
	public static function buildTestQueueName(array $testArgs): string {
		$hash = "{$testArgs['broker_driver']}::{$testArgs['backend_driver']}";
		return "test-queue-$hash";
	}

	public static function getPredisRedisDriver(): PredisRedisDriver {

		$config = [
			'host' => '[::1]', // IPv6 localhost.
			'port' => 40001,
		];

		$predis = new PredisClient($config);
		return new PredisRedisDriver($predis);

	}

	public static function getPhpAmqpLibAmqpDriver(): PhpAmqpLibAmqpDriver {

		$config = [
			'host' => '[::1]', // IPv6 localhost.
			'port' => 40002,
		];

		$amqp = new AMQPStreamConnection(
			host: $config['host'],
			port: $config['port'],
			user: 'guest',
			password: 'guest',
		);

		return new PhpAmqpLibAmqpDriver($amqp);

	}

}
