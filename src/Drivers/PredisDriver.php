<?php

declare(strict_types=1);

namespace Smuuf\CeleryForPhp\Drivers;

use Predis\Client as PredisClient;

use Smuuf\CeleryForPhp\StrictObject;

/**
 * Redis driver backed by Predis.
 */
class PredisDriver implements IRedisDriver {

	use StrictObject;

	public function __construct(
		private PredisClient $predis,
	) {}

	public function getDatabaseIndex(): int {
		/** @var \Predis\Connection\AbstractConnection */
		$connection = $this->predis->getConnection();
		return (int) $connection->getParameters()->database;
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
