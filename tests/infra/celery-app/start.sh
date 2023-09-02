#!/bin/sh

# exec replaces this script's process with the one we're executing - any POSIX
# signals will be then correctly send to Python runtime.
exec python -m celery -A main worker --loglevel=INFO
