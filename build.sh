#!/usr/bin/env bash

set -e

docker build --progress=plain --target tool -t mock-server:tool .

docker build --progress=plain --target server -t mock-server:server .
