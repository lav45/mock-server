<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Functional\Server\Component;

use Amp\Sync\LocalMutex;
use Amp\Sync\Mutex;

final class Storage
{
    private array $data = [];

    private Mutex $mutex;

    public function __construct()
    {
        $this->mutex = new LocalMutex();
    }

    public function add(mixed $value): void
    {
        $this->lock(function () use ($value) {
            $this->data[] = $value;
        });
    }

    public function flush(): array
    {
        return $this->lock(function () {
            $data = $this->data;
            $this->data = [];
            return $data;
        });
    }

    private function lock(callable $fn): mixed
    {
        $lock = $this->mutex->acquire();
        try {
            $result = $fn();
        } finally {
            $lock->release();
        }
        return $result;
    }
}
