<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Service;

interface Parser
{
    public function replace(mixed $data): mixed;

    public function withData(array $data): self;
}
