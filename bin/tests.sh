#!/bin/bash

set -e

cd $(dirname $0)/..

TEST_PATH="${1:-./tests/suite}"

# Test configuration
export CELERYFORPHP_TASK_SERIALIZER='json'
export CELERYFORPHP_TASK_MESSAGE_PROTOCOL_VERSION=2

function _info {
	echo -e "▄"
	echo -e "█ $@"
	echo -e "▀"
}

function _compose {
	docker compose -f ./tests/infra/docker/test-services.yml $@
}

_info "Starting test infrastructure services"
_compose up \
	--detach \
	--quiet-pull \
	--build

_info "Tests will use this configuration:"
echo CELERYFORPHP_TASK_SERIALIZER=$CELERYFORPHP_TASK_SERIALIZER
echo CELERYFORPHP_TASK_MESSAGE_PROTOCOL_VERSION=$CELERYFORPHP_TASK_MESSAGE_PROTOCOL_VERSION

_info "Running tests"
php ./vendor/nette/tester/src/tester \
		-C `# Use system-wide php-ini` \
		--coverage ./tests/output/coverage.html \
		--coverage-src ./src \
		-p phpdbg \
		$TEST_PATH

_info "Stopping test infrastructure services"
_compose down --timeout 2
