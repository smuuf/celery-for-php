<?php

use Smuuf\CeleryForPhp\Config;
use Tester\Assert;

use Smuuf\CeleryForPhp\DeliveryInfo;
use Smuuf\CeleryForPhp\Interfaces\IBroker;
use Smuuf\CeleryForPhp\Messaging\CeleryMessage;
use Smuuf\CeleryForPhp\Messaging\MessageBuilder;
use Smuuf\CeleryForPhp\Producer;
use Smuuf\CeleryForPhp\Serializers\JsonSerializer;
use Smuuf\CeleryForPhp\TaskSignature;

require __DIR__ . '/../bootstrap.php';

$fakeBroker = new class() implements IBroker {

	private $last = [];

	/**
	 * @return array{CeleryMessage, DeliveryInfo}
	 */
	public function getLastMessage(): array {
		return $this->last;
	}

	public function publish(
		CeleryMessage $msg,
		DeliveryInfo $deliveryInfo,
	): void {
		$this->last = [$msg, $deliveryInfo];
	}

};

$config = Config::createDefault();
$producer = new Producer(
	$fakeBroker,
	$config,
	new MessageBuilder(new JsonSerializer()),
);

$si = new TaskSignature('lol_task', queue: 'some_queue', kwargs: ['a' => 1]);
$taskId = $producer->publishTask($si);
[$lastMsg, $lastDeliveryInfo] = $fakeBroker->getLastMessage();

/** @var CeleryMessage $lastMsg */
/** @var DeliveryInfo $lastDeliveryInfo */
Assert::truthy($taskId);
Assert::truthy($lastMsg->getProperties()['delivery_info']);
Assert::same('', $lastDeliveryInfo->getExchange());
Assert::same('some_queue', $lastDeliveryInfo->getRoutingKey());

$producer->publishControl(
	command: 'my_command',
	args: ['arg_1' => 1, 'arg_2' => 2],
);
[$lastMsg, $lastDeliveryInfo] = $fakeBroker->getLastMessage();

/** @var CeleryMessage $lastMsg */
/** @var DeliveryInfo $lastDeliveryInfo */
Assert::truthy($lastMsg->getProperties()['delivery_info']);
Assert::same($config->getControlExchangeName() . ".pidbox", $lastDeliveryInfo->getExchange());
Assert::same('', $lastDeliveryInfo->getRoutingKey());
