<?php declare(strict_types=1);

namespace lav45\MockServer\Mock;

use lav45\MockServer\InvalidConfigException;

trait DataTypeTrait
{
    private string|null $type = null;

    public function getType(string $default = null): string|null
    {
        return $this->type ?? $default;
    }

    protected function setType(string $type): void
    {
        if ($this->type) {
            throw new InvalidConfigException("You can't use `{$type}` and `{$this->type}` at the same time");
        }
        $this->type = $type;
    }
}