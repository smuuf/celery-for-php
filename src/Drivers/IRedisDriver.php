<?php

declare(strict_types=1);

namespace Smuuf\CeleryForPhp\Drivers;

interface IRedisDriver {

	public function getDatabaseIndex(): int;
	public function get(string $key): mixed;

	public function set(
		string $key,
		string $value,
		?float $ttlSeconds = null,
	): void;

	public function del(string $key): void;
	public function lpush(string $key, string $value): void;
	public function publish(string $key, string $value): void;

}
