<?php

namespace Smuuf\CeleryForPhp;

use Smuuf\CeleryForPhp\Exc\InvalidArgumentException;
use Smuuf\CeleryForPhp\Helpers\DefaultTaskIdFactory;
use Smuuf\CeleryForPhp\Interfaces\ISerializer;
use Smuuf\CeleryForPhp\Messaging\MessageBuilder;
use Smuuf\CeleryForPhp\Interfaces\ITaskIdFactory;
use Smuuf\CeleryForPhp\Serializers\JsonSerializer;

class Config {

	use StrictObject;

	public static function createDefault(): self {
		return new self();
	}

	/**
	 * @param int $taskMessageProtocolVersion
	 *     Celery task message protocol version to use.
	 *     See https://docs.celeryq.dev/en/stable/internals/protocol.html
	 * @param ISerializer $taskSerializer
	 *     Serializer to use when serializing task message body. If not
	 *     specified, `JsonSerializer` is used by default.
	 * @param null|ITaskIdFactory $taskIdFactory
	 *     Optional custom task ID builder. If not specified, a default
	 *     factory will be used.
	 * @param string $controlExchangeName
	 *     Name of the Celery control exchange (used for sending control commands,
	 *     such as 'revoke', to Celery workers).
	 *     Default value is 'celery' but can be specified to some other name.
	 */
	public function __construct(
		private int $taskMessageProtocolVersion = MessageBuilder::MESSAGE_PROTOCOL_V2,
		private ?ISerializer $taskSerializer = null,
		private ?ITaskIdFactory $taskIdFactory = null,
		private string $controlExchangeName = 'celery',
	) {

		$this->taskIdFactory ??= new DefaultTaskIdFactory();
		$this->taskSerializer ??= new JsonSerializer();

		if (!strlen($this->controlExchangeName)) {
			throw new InvalidArgumentException('Control exchange name must be a non-empty string');
		}

		if (!in_array($this->taskMessageProtocolVersion, MessageBuilder::VALID_MESSAGE_PROTOCOL_VERSIONS, true)) {
			throw new InvalidArgumentException("Invalid message protocol version '{$this->taskMessageProtocolVersion}'");
		}

	}

	public function getTaskIdFactory(): ITaskIdFactory {
		return $this->taskIdFactory;
	}

	public function getControlExchangeName(): string {
		return $this->controlExchangeName;
	}

	public function getTaskProtocolVersion(): int {
		return $this->taskMessageProtocolVersion;
	}

	public function getTaskSerializer(): ISerializer {
		return $this->taskSerializer;
	}

}
