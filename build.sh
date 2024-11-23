#!/usr/bin/env bash

set -e

docker build -f docker/base.Dockerfile -t mock-server-base .
docker build -f docker/dev.Dockerfile -t mock-server-dev .
docker build -f docker/prod.Dockerfile -t mock-server-prod .