#!/bin/bash

cd $(dirname $0)/../
./vendor/bin/phpstan analyze --level=5 src
