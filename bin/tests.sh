#!/bin/sh

#
# NOTE: IFs must have sh syntax (not bash), because slim testing containers
# don't have bash, only sh.
#
set -e

cd $(dirname $0)/..

if [ "$1" = "--local" ]; then
	php ./vendor/nette/tester/src/tester \
			-o log \
			--coverage ./tests/output/coverage.html \
			--coverage-src ./src \
			-p phpdbg \
			./tests/suite;
	exit
fi

if [ "$DOCKER_TESTS" = "1" ]; then
	exec php ./vendor/nette/tester/src/tester \
		-o log \
		--coverage ./tests/output/coverage.html \
		--coverage-src ./src \
		-p phpdbg \
		./tests/suite;
	exit
fi

docker compose -f ./tests/infra/docker/test-services.yml up \
	--quiet-pull \
	--build \
	--abort-on-container-exit

docker compose -f ./tests/infra/docker/test-services.yml rm --force
