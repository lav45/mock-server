<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Parser;

interface InlineParser
{
    public function replace(mixed $data): mixed;
}
