<?php

use Tester\Assert;

use Smuuf\CeleryForPhp\Serializers\JsonSerializer;

require __DIR__ . '/../bootstrap.php';

$s = new JsonSerializer();

//
// Serializing JSOn with broken unicode doesn't fail completely.
// Broken unicode will be substituted with "\ufffd" (REPLACEMENT CHARACTER).
//

$brokenUnicode = "\xC3Hello";
Assert::same('"\ufffdHello"', $s->encode("\xC3Hello"));

//
// JSON encoded crashes if the encoding is not successful, instead of returning
// false.
//

$unEncodable = [STDERR]; // File descriptor cannot be JSON-encoded.
Assert::exception(fn() => $s->encode($unEncodable), \JsonException::class);
