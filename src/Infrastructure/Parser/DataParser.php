<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Parser;

interface DataParser extends InlineParser
{
    public function withData(array $data): self;
}
