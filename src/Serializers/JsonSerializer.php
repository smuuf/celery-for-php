<?php

namespace Smuuf\CeleryForPhp\Serializers;

use Smuuf\CeleryForPhp\Interfaces\ISerializer;

class JsonSerializer implements ISerializer {

	private const JSON_ENCODE_FLAGS
		= JSON_THROW_ON_ERROR
		| JSON_INVALID_UTF8_SUBSTITUTE;

	public function getContentType(): string {
		return "application/json";
	}

	public function encode(mixed $input): string {
		return json_encode($input, self::JSON_ENCODE_FLAGS);
	}

	/**
	 * @return array<mixed>
	 */
	public function decode(string $input): array {
		return json_decode($input, associative: true);
	}

}
