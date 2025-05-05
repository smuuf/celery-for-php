<?php

declare(strict_types=1);

namespace Smuuf\CeleryForPhp\Drivers;

use Predis\Client as PredisClient;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\Replication\ReplicationInterface;

use Smuuf\CeleryForPhp\StrictObject;
use Smuuf\CeleryForPhp\Exc\RuntimeException;

/**
 * Redis driver backed by Predis.
 */
class PredisRedisDriver implements IRedisDriver {

	use StrictObject;

	public function __construct(
		private PredisClient $predis,
	) {}

	public function getDatabaseIndex(): int {
		$connection = $this->predis->getConnection();

		if ($connection instanceof NodeConnectionInterface) {
			return (int) $connection->getParameters()->database;
		}

		if ($connection instanceof ReplicationInterface) {
			return (int) $connection->getMaster()->getParameters()->database;
		}

		throw new RuntimeException(sprintf(
			"Cannot retrieve database index from '%s'",
			get_debug_type($connection),
		));
	}

	public function get(string $key): mixed {
		return $this->predis->get($key);
	}

	public function set(
		string $key,
		string $value,
		?float $ttlSeconds = null,
	): void {
		$this->predis->set($key, $value);
	}

	public function del(string $key): void {
		$this->predis->del($key);
	}

	public function lpush(string $key, string $value): void {
		$this->predis->lpush($key, [$value]);
	}

	public function publish(string $channel, string $message): void {
		$this->predis->publish($channel, $message);
	}

}
