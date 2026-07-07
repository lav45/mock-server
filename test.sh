#!/usr/bin/env sh

set -e

./composer cs:fix
./composer check
./unit-test.sh coverage:html
./e2e-test.sh
./composer deptrac
./composer unused
./benchmark.sh
