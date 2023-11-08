#!/usr/bin/env bash

build() {
  docker build -f Dockerfile -t mock-server .
  docker build -f dev.Dockerfile -t mock-server-dev .
}

RES=$(docker image inspect mock-server-dev | grep '"Id": "sha256:')
if [[ -z $RES ]]; then
  build
fi

composer install --no-dev --optimize-autoloader --no-progress --prefer-dist --no-cache

build