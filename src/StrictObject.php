<?php

declare(strict_types=1);

namespace Smuuf\CeleryForPhp;

trait StrictObject {

	/**
	 * Used when trying to access undeclared or inaccessible property.
	 *
	 * @throws \LogicException
	 */
	public function __get(string $name): mixed {

		throw new \LogicException(sprintf(
			"Cannot read an undeclared property '%s' in %s.",
			$name,
			get_called_class(),
		));

	}

	/**
	 * Used when trying to write to any undeclared or inaccessible property.
	 *
	 * @throw \LogicException
	 */
	public function __set(string $name, mixed $value): void {

		throw new \LogicException(sprintf(
			"Cannot write to an undeclared property '%s' in %s",
			$name,
			get_called_class(),
		));

	}

}
