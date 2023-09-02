<?php

use Tester\Assert;

use Smuuf\CeleryForPhp\DeliveryInfo;
use Smuuf\CeleryForPhp\Messaging\CeleryMessage;
use Smuuf\CeleryForPhp\Serializers\JsonSerializer;

require __DIR__ . '/../bootstrap.php';

$headers = [
	'header_1' => 'ahoj_header',
	'header_2' => 'vole_header',
];

$properties = [
	'prop_1' => 'ahoj_prop',
	'prop_2' => 'vole_prop',
];

$body = [
	'body_1' => 'ahoj_body',
	'body_2' => 'vole_body',
];

$serializedBody = json_encode($body);
$msg = new CeleryMessage($headers, $properties, $body, new JsonSerializer());

Assert::same($headers, $msg->getHeaders());
Assert::same($properties, $msg->getProperties());
Assert::same($body, $msg->getBody());
Assert::same($serializedBody, $msg->getSerializedBody());

Assert::equal([
	'content-encoding' => 'utf-8',
	'content-type' => 'application/json',
	'headers' => $headers,
	'properties' => $properties,
	'body' => $serializedBody,
	'headers' => $headers,
], $msg->asArray());

// Inject delivery info.

$msg->injectDeliveryInfo(new DeliveryInfo(exchange: 'di_exchange', routingKey: 'di_routing_key'));
$expectedNewProperties = [
	'prop_1' => 'ahoj_prop',
	'prop_2' => 'vole_prop',
	'delivery_info' => [
		'exchange' => 'di_exchange',
		'routing_key' => 'di_routing_key',
	],
];

Assert::equal($expectedNewProperties, $msg->getProperties());
Assert::equal([
	'content-encoding' => 'utf-8',
	'content-type' => 'application/json',
	'headers' => $headers,
	'properties' => $expectedNewProperties,
	'body' => $serializedBody,
	'headers' => $headers,
], $msg->asArray());
