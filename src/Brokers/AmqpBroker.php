<?php

declare(strict_types=1);

namespace Smuuf\CeleryForPhp\Brokers;

use Smuuf\CeleryForPhp\DeliveryInfo;
use Smuuf\CeleryForPhp\StrictObject;
use Smuuf\CeleryForPhp\Drivers\IAmqpDriver;
use Smuuf\CeleryForPhp\Messaging\CeleryMessage;
use Smuuf\CeleryForPhp\Interfaces\IBroker;

class AmqpBroker implements IBroker {

	use StrictObject;

	public function __construct(
		private IAmqpDriver $amqp,
	) {}

	public function publish(
		CeleryMessage $msg,
		DeliveryInfo $deliveryInfo,
	): void {

		$routingKey = $deliveryInfo->getRoutingKey();

		$msgArr = $msg->asArray();

		$body = $msgArr['body'];
		$headers = $msgArr['headers'];
		$properties = $msgArr['properties'];
		$properties['content_type'] = $msgArr['content-type'];
		$properties['content_encoding'] = $msgArr['content-encoding'];

		if ($exchange = $deliveryInfo->getExchange()) {
			$this->amqp->publish('', $exchange, '', $body, $properties, $headers);
		} else {
			$this->amqp->publish($routingKey, $routingKey, $routingKey, $body, $properties, $headers);
		}

	}

}
