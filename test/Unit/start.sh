#!/usr/bin/env sh

set -e

SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
ROOT_DIR=$(cd "$SCRIPT_DIR/../.." && pwd)

CMD=${1:-phpunit}
DIR=${2:-test/Unit}

if [ $# -ge 2 ]; then
  shift 2
elif [ $# -eq 1 ]; then
  shift 1
fi

cd "$ROOT_DIR"
./composer "$CMD" "$DIR" "$@"