<?php

namespace Smuuf\CeleryForPhp\Interfaces;

interface ISerializer {

	public function getContentType(): string;
	public function encode(mixed $input): string;
	public function decode(string $input): array;

}
