#!/bin/bash

cd $(dirname $0)/..

ARG="$1"
[[ $ARG == "--fix" ]] && COMMAND="fix" || COMMAND="check"

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
