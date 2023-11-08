#!/bin/bash

set -e

cd $(dirname $0)/..

TEST_PATH="${1:-./tests/suite}"

function _info {
	echo -e "▄"
	echo -e "█ $@"
	echo -e "▀"
}

function _compose {
	docker compose -f ./tests/infra/docker/test-services.yml $@
}

_info "Maybe cleaning up previous test services"
_compose down --timeout 0 1>/dev/null 2>&1 || true

_info "Starting test services"
_compose up \
	--detach \
	--wait \
	--quiet-pull \
	--build

_info "Running tests"
php ./vendor/nette/tester/src/tester \
		-C `# Use system-wide php-ini` \
		--coverage ./tests/output/coverage.html \
		--coverage-src ./src \
		--log ./tests/output/tests.log \
		-p phpdbg \
		$TEST_PATH \
|| true # Continue even with failed tests.

_info "Stopping test services"
_compose down --timeout 2
