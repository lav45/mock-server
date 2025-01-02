#!/usr/bin/env bash

set -e

docker build --pull -f docker/base.Dockerfile -t mock-server-base .
docker build -f docker/dev.Dockerfile -t mock-server-dev .

./composer install --prefer-dist --ansi

docker build -f docker/prod.Dockerfile -t mock-server-prod .