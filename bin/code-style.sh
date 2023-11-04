#!/bin/bash

cd $(dirname $0)/..

ARG="$1"
[[ $ARG == "--fix" ]] && COMMAND="fix" || COMMAND="check"

# php-cs-fixer may lag behind with supporting newest versions of PHP and
# would complain when running under them, which would needlessly fail our
# GitHub Action. So we'll tell php-cs-fixer to skip that check.
export PHP_CS_FIXER_IGNORE_ENV=1

./vendor/bin/php-cs-fixer $COMMAND --diff -vvv
EXITCODE=$?

if [[ $COMMAND == "check" && $EXITCODE -gt 0 ]]; then
	echo
	echo "█ Oh noes, php-cs-fixer found issues with code style."
	echo "█ Run '$0 --fix' to fix them."
fi

if [[ $EXITCODE -eq 0 ]]; then
	echo
	echo "█ Everything went ok."
fi

# Bubble up the exit code to the original caller.
exit $EXITCODE
