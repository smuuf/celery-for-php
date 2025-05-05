#!/bin/bash

cd $(dirname $0)/../
./vendor/bin/phpstan analyze --configuration ./phpstan.neon
