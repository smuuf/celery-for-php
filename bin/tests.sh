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

# NOTE: We would use Nette Tester argument "-p phpdbg" to run tests with phpdbg,
# but phpdbg8.1 fails with segfaults. When we're past PHP 8.1, we can use phpdbg
# again.
TESTS_EXIST_CODE=0
_info "Running tests"
php ./vendor/nette/tester/src/tester \
		-C `# Use system-wide php-ini` \
		--coverage ./tests/output/coverage.html \
		--coverage-src ./src \
		--log ./tests/output/tests.log \
		$TEST_PATH \
|| TESTS_EXIST_CODE=$? # Continue even with failed tests but obtain exit code.

_info "Tests exit code: $TESTS_EXIST_CODE"

_info "Stopping test services"
_compose down --timeout 2

exit $TESTS_EXIST_CODE
