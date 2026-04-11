<?php declare(strict_types=1);

namespace Lav45\MockServer\Http;

interface RequestFactory
{
    public function withData(array $data): self;
}
