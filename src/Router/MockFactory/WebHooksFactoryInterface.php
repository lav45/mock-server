<?php declare(strict_types=1);

namespace Lav45\MockServer\Router\MockFactory;

use Lav45\MockServer\Parser\VariableParser;

interface WebHooksFactoryInterface
{
    public function create(VariableParser $parser, array $data): iterable;
}
