#!/usr/bin/env bash

set -e

docker build --no-cache -f docker/base.Dockerfile -t mock-server-base .
docker build --no-cache -f docker/dev.Dockerfile -t mock-server-dev .