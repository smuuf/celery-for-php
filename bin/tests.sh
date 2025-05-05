#!/bin/bash

#
# Test-execution helper.
#
# This can be used both locally and in GHA workflows.
#
# Usage examples:
# ./tests.sh
#   1. Setup test services (Redis, RabbitMQ, etc.).
#   2. Run tests in default path.
#   3. Tear down test services.
# ./tests.sh ./my/test/dir
#   1. Setup test services (Redis, RabbitMQ, etc.).
#   2. Run tests in specified path.
#   3. Tear down test services.
# ./tests.sh -s infra:setup|infra:teardown
#   1. Only Setup or tear down test services (Redis, RabbitMQ, etc.).
#
# This is a bit complex to allow all-in-one testing (locally, for example) or
# doing required stages separately (in GHA, where we want services
# setup/teardown to be in separate workflow steps).

set -e

cd $(dirname $0)/..

# Constants.
STAGE_INFRA_SETUP='infra:setup'
STAGE_INFRA_TEARDOWN='infra:teardown'
STAGE_TESTS='tests'

# Defaults.
STAGE=""
TEST_PATH="./tests/suite"

# Parse options
while getopts ":s:" opt; do
	case $opt in
		s)
			STAGE="$OPTARG"
			;;
		\?)
			echo "Invalid option: -$OPTARG" >&2
			exit 1
			;;
		:)
			echo "Option -$OPTARG requires an argument." >&2
			exit 1
			;;
	esac
done
shift $((OPTIND - 1))

# Positional argument (TEST_PATH)
if [ $# -ge 1 ]; then
	TEST_PATH="$1"
fi

if [[ -n "$STAGE" && "$STAGE" != "$STAGE_INFRA_SETUP" && "$STAGE" != "$STAGE_INFRA_TEARDOWN" && "$STAGE" != "$STAGE_TESTS" ]]; then
	echo "Error: $STAGE must be '$STAGE_INFRA_SETUP' or '$STAGE_INFRA_TEARDOWN' or '$STAGE_TESTS'"
	exit 1
fi

function _info {
	echo -e "▄"
	echo -e "█ $@"
	echo -e "▀"
}

function _compose {
	docker compose -f ./tests/infra/docker/test-services.yml $@
}

# Only if STAGE is empty or during a setup stage.
if [[ -z "$STAGE" || "$STAGE" == "$STAGE_INFRA_SETUP" ]]; then

	_info "Maybe cleaning up previous test services"
	_compose down --timeout 0 1>/dev/null 2>&1 || true

	_info "Starting test services"
	_compose up \
		--detach \
		--wait \
		--quiet-pull \
		--build

fi

# NOTE: We would use Nette Tester argument "-p phpdbg" to run tests with phpdbg,
# but phpdbg8.1 fails with segfaults. When we're past PHP 8.1, we can use phpdbg
# again.
TESTS_EXIST_CODE=0

# Only execute if STAGE is empty if tests should be executed.
if [[ -z "$STAGE" || "$STAGE" == "$STAGE_TESTS" ]]; then

	_info "Running tests"
	php ./vendor/nette/tester/src/tester \
			-C `# Use system-wide php-ini` \
			-o console-lines \
			--coverage ./tests/output/coverage.html \
			--coverage-src ./src \
			--log ./tests/output/tests.log \
			$TEST_PATH \
	|| TESTS_EXIST_CODE=$? # Continue even with failed tests but obtain exit code.

	_info "Tests exit code: $TESTS_EXIST_CODE"

fi

# Only if STAGE is empty or during a teardown stage.
if [[ -z "$STAGE" || "$STAGE" == "$STAGE_INFRA_TEARDOWN" ]]; then
	_info "Stopping test services"
	_compose down --timeout 2
fi

exit $TESTS_EXIST_CODE
