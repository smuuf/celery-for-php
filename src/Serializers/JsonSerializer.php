<?php

namespace Smuuf\CeleryForPhp\Serializers;

use Smuuf\CeleryForPhp\Interfaces\ISerializer;

class JsonSerializer implements ISerializer {

	public function getContentType(): string {
		return "application/json";
	}

	public function encode(mixed $input): string {
		return json_encode($input, JSON_UNESCAPED_SLASHES);
	}

	/**
	 * @return array<mixed>
	 */
	public function decode(string $input): array {
		return json_decode($input, associative: true);
	}

}
