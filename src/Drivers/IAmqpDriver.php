<?php

declare(strict_types=1);

namespace Smuuf\CeleryForPhp\Drivers;

interface IAmqpDriver {

	public function publish(string $queue, string $exchange, string $routingKey, string $message, array $properties, array $headers): void;

	public function deleteExchange(string $exchange): void;

	public function deleteQueue(string $queue): void;

	public function purgeQueue(string $queue): void;

	public function queueLength(string $queue): int;

}
