<?php

namespace Smuuf\CeleryForPhp\Interfaces;

use Smuuf\CeleryForPhp\TaskMetaResult;

interface IResultBackend {

	public function getTaskMetaResult(string $taskId): TaskMetaResult;

	public function storeResult(
		string $taskId,
		mixed $result,
		string $state,
		?string $traceback = null,
	): void;

	public function forgetResult(string $taskId): void;

}
