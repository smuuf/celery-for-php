#!/bin/bash

set -e

cd $(dirname $0)/..

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

_info "Running tests"
php ./vendor/nette/tester/src/tester \
		--coverage ./tests/output/coverage.html \
		--coverage-src ./src \
		-p phpdbg \
		./tests/suite;

_info "Stopping test infrastructure services"
_compose down --timeout 2
