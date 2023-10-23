<?php

declare(strict_types=1);

namespace Smuuf\CeleryForPhp\Helpers;

use Smuuf\CeleryForPhp\Interfaces\ITaskIdFactory;

class DefaultTaskIdFactory implements ITaskIdFactory {

	public function buildTaskId(
		string $taskName,
		array $args,
		array $kwargs,
	): string {
		return sprintf('c4p:%s', Functions::uuid4());
	}
}
