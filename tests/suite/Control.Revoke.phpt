<?php

use Tester\Assert;

use Smuuf\CeleryForPhp\Celery;
use Smuuf\CeleryForPhp\Config;
use Smuuf\CeleryForPhp\Helpers\Signals;
use Smuuf\CeleryForPhp\DeliveryInfo;
use Smuuf\CeleryForPhp\TaskMetaResult;
use Smuuf\CeleryForPhp\Messaging\CeleryMessage;
use Smuuf\CeleryForPhp\Interfaces\IBroker;
use Smuuf\CeleryForPhp\Interfaces\IResultBackend;

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

$fakeResultBackend = new class() implements IResultBackend {

	public function getTaskMetaResult(string $taskId): TaskMetaResult {
		return new TaskMetaResult('meh');
	}

	public function storeResult(
		string $taskId,
		mixed $result,
		string $state,
		?string $traceback = null,
	): void {}

	public function forgetResult(string $taskId): void {}


};

$config = Config::createDefault();
$celery = new Celery($fakeBroker, $fakeResultBackend, $config);

$celery->getControl()->revoke('whatever_task_id', terminate: true, signal: Signals::SIGKILL);
[$lastMsg, $lastDeliveryInfo] = $fakeBroker->getLastMessage();

/** @var CeleryMessage $lastMsg */
/** @var DeliveryInfo $lastDeliveryInfo */
Assert::truthy($lastMsg->getProperties()['delivery_info']);
Assert::same($config->getControlExchangeName() . ".pidbox", $lastDeliveryInfo->getExchange());
Assert::same('', $lastDeliveryInfo->getRoutingKey());

$unserializedBody = $config->getTaskSerializer()->decode($lastMsg->getSerializedBody());
Assert::same([
	'method' => 'revoke',
	'arguments' => [
		'task_id' => 'whatever_task_id',
		'terminate' => true,
		'signal' => Signals::SIGKILL,
	],
	'destination' => null,
	'pattern' => null,
	'matcher' => null,
], $unserializedBody);
