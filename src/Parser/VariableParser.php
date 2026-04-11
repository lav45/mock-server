<?php declare(strict_types=1);

namespace Lav45\MockServer\Parser;

interface VariableParser extends InlineParser
{
    public function withData(array $data): self;
}
