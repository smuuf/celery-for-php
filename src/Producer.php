<?php

namespace Smuuf\CeleryForPhp;

use Smuuf\CeleryForPhp\Interfaces\IBroker;
use Smuuf\CeleryForPhp\Messaging\MessageBuilder;

class Producer {

	use StrictObject;

	/** @var string Celery control Mailbox/Pidbox exchange name format. */
	private const CONTROL_EXCHANGE_NAME_TEMPLATE = '%s.pidbox';

	public function __construct(
		private IBroker $broker,
		private Config $config,
		private MessageBuilder $messageBuilder,
	) {}

	public function publishTask(TaskSignature $si): string {

		$deliveryInfo = new DeliveryInfo(routingKey: $si->getQueue());

		$taskId = $this->config->getTaskIdFactory()->buildTaskId(
			taskName: $si->getTaskName(),
			args: $si->getArgs(),
			kwargs: $si->getKwargs(),
		);

		$message = $this->messageBuilder->buildTaskMessage(
			$this->config->getTaskProtocolVersion(),
			$taskId,
			$si,
		);

		$message->injectDeliveryInfo($deliveryInfo);
		$this->broker->publish($message, $deliveryInfo);
		return $taskId;

	}

	public function publishControl(
		string $command,
		array $args = [],
		?string $destination = null,
		?string $pattern = null,
		?string $matcher = null,
	): void {

		$controlExchangeName = sprintf(
			self::CONTROL_EXCHANGE_NAME_TEMPLATE,
			$this->config->getControlExchangeName(),
		);

		$deliveryInfo = new DeliveryInfo(exchange: $controlExchangeName);
		$message = $this->messageBuilder->buildControlMessage(
			$command,
			$args,
			$destination,
			$pattern,
			$matcher,
		);

		$message->injectDeliveryInfo($deliveryInfo);
		$this->broker->publish($message, $deliveryInfo);

	}

}
