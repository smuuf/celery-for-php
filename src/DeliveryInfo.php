<?php

namespace Smuuf\CeleryForPhp;

final class DeliveryInfo {

	use StrictObject;

	public function __construct(
		private string $exchange = '',
		private string $routingKey = '',
	) {}

	public function getExchange(): string {
		return $this->exchange;
	}

	public function getRoutingKey(): string {
		return $this->routingKey;
	}

}
