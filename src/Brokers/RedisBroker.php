<?php

declare(strict_types=1);

namespace Smuuf\CeleryForPhp\Brokers;

use Smuuf\CeleryForPhp\DeliveryInfo;
use Smuuf\CeleryForPhp\StrictObject;
use Smuuf\CeleryForPhp\Drivers\IRedisDriver;
use Smuuf\CeleryForPhp\Messaging\CeleryMessage;
use Smuuf\CeleryForPhp\Interfaces\IBroker;

class RedisBroker implements IBroker {

	use StrictObject;

	/**
	 * Fanout key prefix. Redis's PUBSUB semantics work across Redis databases,
	 * so PUBLISH on DB 0 can be received by clients on DB 16. Thus, we need
	 * to add a prefix to the fanout key, so the message is received only by
	 * clients of correct DB index.
	 *
	 * Default exchange name for Celery using DB 0 is "/0.celery.pidbox".
	 *
	 * For more details see `kombu/transport/redis.py` in Kombu library.
	 *
	 * @see https://redis.io/docs/interact/pubsub/
	 * @var string
	 */
	private const KEYPREFIX_FANOUT_TEMPLATE = '/%d';

	public function __construct(
		private IRedisDriver $redis,
	) {}

	public function publish(
		CeleryMessage $msg,
		DeliveryInfo $deliveryInfo,
	): void {

		if ($exchange = $deliveryInfo->getExchange()) {
			$this->sendFanout($msg, $exchange);
		} else {
			$this->sendDirect($msg, $deliveryInfo->getRoutingKey());
		}

	}

	/**
	 * @return array<string, mixed>
	 */
	private function getFinalMessageWithEncoding(
		CeleryMessage $msg,
	): array {

		// Apply additional base64 encoding to the serialized body.
		$data = $msg->asArray();
		$data['body'] = base64_encode($data['body']);
		$data['properties']['body_encoding'] = 'base64';
		return $data;

	}

	private function sendDirect(CeleryMessage $msg, string $queue): void {

		$json = json_encode(
			$this->getFinalMessageWithEncoding($msg),
			JSON_UNESCAPED_SLASHES,
		);

		$this->redis->lpush($queue, $json);

	}

	private function sendFanout(
		CeleryMessage $msg,
		string $exchange,
	): void {

		$json = json_encode(
			$this->getFinalMessageWithEncoding($msg),
			JSON_UNESCAPED_SLASHES,
		);

		$dbIndex = $this->redis->getDatabaseIndex();
		$prefix = sprintf(self::KEYPREFIX_FANOUT_TEMPLATE, $dbIndex);
		$channel = "$prefix.$exchange";

		$this->redis->publish($channel, $json);

	}

}
