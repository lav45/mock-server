<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Parser;

interface ParserFactoryInterface
{
    public function create(): DataParser;
}
