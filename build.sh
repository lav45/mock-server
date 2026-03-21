#!/usr/bin/env bash

set -eux

docker build --progress=plain --target tool -t mock-server:tool .

docker build --progress=plain --target base-server -t mock-server:server .
