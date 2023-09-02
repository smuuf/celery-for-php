<?php

namespace Smuuf\CeleryForPhp;

use Smuuf\CeleryForPhp\Helpers\Signals;

class Control {

	use StrictObject;

	/**
	 * @param Producer $producer Message producer/publisher.
	 */
	public function __construct(
		private Producer $producer,
	) {}

	public function revoke(
		string $taskId,
		bool $terminate = false,
		string $signal = Signals::SIGTERM,
	): void {

		$this->producer->publishControl(
			command: 'revoke',
			args: [
				'task_id' => $taskId,
				'terminate' => $terminate,
				'signal' => $signal,
			],
		);

	}

}
