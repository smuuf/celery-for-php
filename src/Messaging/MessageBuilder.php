<?php

declare(strict_types=1);

namespace Smuuf\CeleryForPhp\Messaging;

use Smuuf\CeleryForPhp\TaskSignature;
use Smuuf\CeleryForPhp\Exc\InvalidArgumentException;
use Smuuf\CeleryForPhp\Helpers\Functions;
use Smuuf\CeleryForPhp\Interfaces\ISerializer;

/**
 * @see https://docs.celeryproject.org/en/stable/internals/protocol.html#task-messages
 */
class MessageBuilder {

	public const MESSAGE_PROTOCOL_V1 = 1;
	public const MESSAGE_PROTOCOL_V2 = 2;

	public const VALID_MESSAGE_PROTOCOL_VERSIONS = [
		self::MESSAGE_PROTOCOL_V1,
		self::MESSAGE_PROTOCOL_V2,
	];

	public function __construct(
		private ISerializer $serializer,
	) {}

	/**
	 * Format a control message that can be sent to Celery workers to do
	 * something _(for example revoke a task)_.
	 *
	 * For more details about `$destination`, `$pattern` and `$matcher` go
	 * see `kombu/matcher.py` and `Node.handle_message()` in `kombu/pidbox.py`
	 * in Kombu library.
	 *
	 * @param string $command Command to send.
	 * @param array<mixed> $args Arguments for the command.
	 * @param ?string $destination (Optional) Destination hostname.
	 * @param ?string $pattern (Optional) Pattern to use for matching the
	 *     hostname.
	 * @param ?string $matcher Matcher to use when matching pattern.
	 *     By default Celery/Kombu supports 'glob' (default if `$matcher` is
	 *     `null`) or 'pcre'.
	 */
	public function buildControlMessage(
		string $command,
		array $args = [],
		?string $destination = null,
		?string $pattern = null,
		?string $matcher = null,
	): CeleryMessage {

		$body = [
			'method' => $command,
			'arguments' => $args,
			'destination' => $destination,
			'pattern' => $pattern,
			'matcher' => $matcher,
		];

		$headers = [
			"clock" => 1,
			'expires' => time() + 10,
		];

		$properties = [
			'body_encoding' => 'base64',
			'delivery_mode' => 2,
			'delivery_tag' => Functions::uuid4(),
			'priority' => 0,
		];

		return new CeleryMessage($headers, $properties, $body, $this->serializer);

	}

	public function buildTaskMessage(
		int $taskVersion,
		string $taskId,
		TaskSignature $si,
	): CeleryMessage {

		switch ($taskVersion) {
			case self::MESSAGE_PROTOCOL_V1:
				return self::asTaskVersion1($taskId, $si, $this->serializer);
			case self::MESSAGE_PROTOCOL_V2:
				return self::asTaskVersion2($taskId, $si, $this->serializer);
			default:
				throw new InvalidArgumentException(sprintf(
					'Cannot format task message as unknown version %d',
					$taskVersion,
				));
		}
	}

	private static function asTaskVersion1(
		string $taskId,
		TaskSignature $si,
		ISerializer $serializer,
	): CeleryMessage {

		$headers = [];
		$body = [
			'task' => $si->getTaskName(),
			'id' => $taskId,
			'args' => $si->getArgs(),
			'kwargs' => (object) $si->getKwargs(), // Make sure it's {} if empty.
			'group' => null,
			'retries' => $si->getRetries(),
			'eta' => $si->getEta(),
			'expires' => $si->getExpiration(),
			'utc' => true,
			'callbacks' => null,
			'errbacks' => null,
			'timelimit' => $si->getTimeLimit(),
			'taskset' => null,
			'chord' => null,
		];

		$properties = [
			'correlation_id' => $taskId, // Usually the same as the task ID, often used in amqp to keep track of what a reply is for.
			'reply_to' => $taskId, // Where to send reply to (queue name), used with RPC backend.
			'delivery_mode' => 2, // Persistent mode.
			'delivery_tag' => $taskId,
			'delivery_info' => [
				'priority' => 0,
				'routing_key' => $si->getQueue(),
				'exchange' => '',
			],
		];

		return new CeleryMessage($headers, $properties, $body, $serializer);

	}

	private static function asTaskVersion2(
		string $taskId,
		TaskSignature $si,
		ISerializer $serializer,
	): CeleryMessage {

		$headers = [
			'lang' => 'py',
			'task' => $si->getTaskName(),
			'id' => $taskId,
			'shadow' => null,
			'eta' => $si->getEta(),
			'expires' => $si->getExpiration(),
			'group' => null,
			'group_index' => null,
			'retries' => $si->getRetries(),
			'timelimit' => $si->getTimeLimit(),
			'root_id' => $taskId,
			'parent_id' => null,
			'origin' => Functions::getNodeName(),
			'ignore_result' => false,
			'stamped_headers' => null,
			'stamps' => [],
		];

		$body = [
			$si->getArgs(),
			(object) $si->getKwargs(), // Make sure it's {} if empty.
			[
				'callbacks' => null,
				'errbacks' => null,
				'chain' => null,
				'chord' => null,
			],
		];

		$properties = [
			'correlation_id' => $taskId,
			'reply_to' => '', // Optional.
			'delivery_mode' => 2,
			'delivery_info' => [
				'exchange' => '',
				'routing_key' => $si->getQueue(),
			],
			'priority' => 0,
			'delivery_tag' => Functions::uuid4(),
		];

		return new CeleryMessage($headers, $properties, $body, $serializer);

	}

}
