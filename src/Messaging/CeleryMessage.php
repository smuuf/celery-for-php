<?php

declare(strict_types=1);

namespace Smuuf\CeleryForPhp\Messaging;

use Smuuf\CeleryForPhp\DeliveryInfo;
use Smuuf\CeleryForPhp\Interfaces\ISerializer;

/**
 * Class for representing messages in accordance to Celery message protocol.
 *
 * @see https://docs.celeryq.dev/en/stable/internals/protocol.html
 */
class CeleryMessage {

	/**
	 * @param array<string, mixed> $headers
	 * @param array<string, mixed> $properties
	 * @param array<mixed> $body
	 */
	public function __construct(
		private array $headers,
		private array $properties,
		private array $body,
		private ISerializer $serializer,
	) {}

	/**
	 * @return array<string, mixed>
	 */
	public function getHeaders(): array {
		return $this->headers;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getProperties(): array {
		return $this->properties;
	}

	/**
	 * @return array<mixed>
	 */
	public function getBody(): array {
		return $this->body;
	}

	public function getSerializedBody(): string {
		return $this->serializer->encode($this->body);
	}

	public function injectDeliveryInfo(DeliveryInfo $deliveryInfo): void {

		$this->properties['delivery_info'] = [
			'exchange' => $deliveryInfo->getExchange(),
			'routing_key' => $deliveryInfo->getRoutingKey(),
		];

	}

	/**
	 * @return array{
	 *     'content-encoding': 'utf-8',
	 *     'content-type': string,
	 *     body: string,
	 *     headers: array<string, mixed>,
	 *     properties: array<string, mixed>,
	 * }
	 */
	public function asArray(): array {

		return [
			'content-encoding' => 'utf-8',
			// Content type of the serialized body (not the whole message).
			// Before potentially encoding further by whatever is then present
			// in 'body_encoding' (Celery specific) key inside 'properties'.
			'content-type' => $this->serializer->getContentType(),
			'body' => $this->getSerializedBody(),
			'headers' => $this->headers,
			'properties' => $this->properties,
		];

	}
}
