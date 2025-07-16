#!/usr/bin/env bash

set -e

docker build --progress=plain --target tool -t mock-server:tool .

./composer install --optimize-autoloader --classmap-authoritative --prefer-dist --no-progress --ansi

docker build --progress=plain --target server -t mock-server:server .
