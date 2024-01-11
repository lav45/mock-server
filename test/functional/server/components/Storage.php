<?php declare(strict_types=1);

namespace lav45\MockServer\test\functional\server\components;

class Storage
{
    private array $data = [];

    public function all(): array
    {
        return $this->data;
    }

    public function get(int $index): mixed
    {
        return $this->data[$index] ?? null;
    }

    public function add(mixed $value): void
    {
        $this->set($this->count(), $value);
    }

    public function set(int $index, mixed $value): void
    {
        $this->data[$index] = $value;
    }

    public function count(): int
    {
        return count($this->data);
    }

    public function flush(): void
    {
        $this->data = [];
    }
}