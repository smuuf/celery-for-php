<?php

namespace Smuuf\CeleryForPhp;

use Smuuf\CeleryForPhp\Messaging\MessageBuilder;
use Smuuf\CeleryForPhp\Interfaces\IBroker;
use Smuuf\CeleryForPhp\Interfaces\IResultBackend;

class Celery {

	use StrictObject;

	private Producer $producer;
	private Control $control;

	/**
	 * @param IBroker $broker Message broker for publishing tasks.
	 * @param IResultBackend $backend Result backend for storing task results.
	 */
	public function __construct(
		private IBroker $broker,
		private IResultBackend $backend,
		private ?Config $config = null,
	) {

		$this->config ??= Config::createDefault();
		$messageBuilder = new MessageBuilder($this->config->getTaskSerializer());

		$this->producer = new Producer($this->broker, $this->config, $messageBuilder);
		$this->control = new Control($this->producer);

	}

	public function sendTask(TaskSignature $si): AsyncResult {

		$taskId = $this->producer->publishTask($si);
		return new AsyncResult(
			$taskId,
			$this->backend,
			$this->control,
			$si->getTaskName(),
		);

	}

	public function getAsyncResult(string $taskId): AsyncResult {
		return new AsyncResult($taskId, $this->backend, $this->control);
	}

	public function getControl(): Control {
		return $this->control;
	}

}
