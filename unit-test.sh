#!/usr/bin/env sh

CMD=${1:-phpunit}
DIR=${2:-test/Unit}

if [ $# -ge 2 ]; then
  shift 2
elif [ $# -eq 1 ]; then
  shift 1
fi

./composer "$CMD" "$DIR" "$@"