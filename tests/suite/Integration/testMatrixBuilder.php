<?php

declare(strict_types=1);

if (!class_exists(TestArgumentsPermutator::class)) {
	class TestArgumentsPermutator {

		/**
		 * List of all driver class names (not fully qualified) that will be
		 * tested as brokers. These strings must correcpond to those used for
		 * creating the actual drivers in `tests/bootstrap.php`.
		 *
		 * @var list<class-string>
		 */
		private const BROKER_DRIVERS = [
			'PredisRedisDriver',
			'PhpAmqpLibAmqpDriver',
		];

		/**
		 * List of all driver class names (not fully qualified) that will be
		 * tested as result backend drivers. These strings must correcpond to
		 * those used for creating the actual drivers in `tests/bootstrap.php`.
		 *
		 * @var list<class-string>
		 */
		private const BACKEND_DRIVERS = [
			'PredisRedisDriver',
		];

		private const TASK_SERIALIZERS = [
			'json',
		];

		private const TASK_MESSAGE_PROTOCOL_VERSIONS = [1, 2];

		/**
		 * By default the test argument permutator will create many permutations
		 * of different configurations  which will then to be tested.
		 * This is used when running tests locally - simply running Nette Tester
		 * will test all possible combinations.
		 *
		 * However in Github Actions we want to have these combinations not to
		 * be executed in a single Nette Tester run, but we want these
		 * combinations to be distributed into separate Github Action workflow's
		 * jobs - so we can see the result of each configuration combination
		 * separately and thus better.This is done via GHA matrix strategy,
		 * which uses env variables to specify a fixed value for the args below..
		 *
		 * @param ?string $taskSerializer If specified, this value will be used
		 *     to force a single task serializer to be tested.
		 * @param ?int $taskMessageProtocolVersion If specified, this value will
		 *     be used to force a single task message protocol version to be
		 *     tested.
		 * @param ?string $brokerDriver If specified, this value will be used
		 *     to force a single broker to be tested.
		 * @param ?string $backendDriver If specified, this value will be used
		 *     to force a single backend driver to be tested.
		 */
		public function __construct(
			private ?string $taskSerializer = null,
			private ?int $taskMessageProtocolVersion = null,
			private ?string $brokerDriver = null,
			private ?string $backendDriver = null,
		) {}

		public function getPermutations(): array {

			$struct = [
				'task_serializer' => $this->taskSerializer !== null
						? [$this->taskSerializer]
						: self::TASK_SERIALIZERS,
				'task_message_protocol_version' => $this->taskMessageProtocolVersion !== null
						? [$this->taskMessageProtocolVersion]
						: self::TASK_MESSAGE_PROTOCOL_VERSIONS,
				'broker_driver' => $this->brokerDriver !== null
						? [$this->brokerDriver]
						: self::BROKER_DRIVERS,
				'backend_driver' => $this->backendDriver !== null
						? [$this->backendDriver]
						: self::BACKEND_DRIVERS,
			];

			$keys = array_keys($struct);
			return self::permute($struct, $keys);

		}

		private static function permute(
			array $data,
		): array {

			if (!$data) {
				return [[]];
			}

			$result = [];
			$key = array_key_first($data);
			$choices = $data[$key];

			unset($data[$key]);

			foreach ($choices as $choice) {
				$subPermutations = self::permute($data);
				foreach ($subPermutations as $subPermutation) {
					$result[] = array_merge([$key => $choice], $subPermutation);
				}
			}

			return $result;

		}

	}
}

$taskSerializer = getenv('C4P_TESTS_TASK_SERIALIZER') ?: null;
$taskMessageProtocolVersion = (int) getenv('C4P_TESTS_TASK_MESSAGE_PROTOCOL_VERSION') ?: null;
$brokerDriver = getenv('C4P_TESTS_BROKER_DRIVER') ?: null;
$backendDriver = getenv('C4P_TESTS_BACKEND_DRIVER') ?: null;

$tap = new TestArgumentsPermutator(
	taskSerializer: $taskSerializer,
	taskMessageProtocolVersion: $taskMessageProtocolVersion,
	brokerDriver: $brokerDriver,
	backendDriver: $backendDriver,
);

return $tap->getPermutations();
