<?php

namespace Smuuf\CeleryForPhp\Interfaces;

interface ITaskIdFactory {

	/**
	 * Build a new task ID.
	 *
	 * @param list<mixed> $args
	 * @param array<string, mixed> $kwargs
	 */
	public function buildTaskId(
		string $taskName,
		array $args,
		array $kwargs,
	): string;

}
