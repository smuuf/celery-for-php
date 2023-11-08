#!/bin/sh

cd $(dirname $0)

# exec replaces this script's process with the one we're executing - any POSIX
# signals will be then correctly send to Python runtime.
exec python ./spawner.py
