<?php declare(strict_types=1);

namespace Lav45\MockServer\Router\MockFactory;

use Lav45\MockServer\Domain\Mock\Response;
use Lav45\MockServer\Http\RequestData;
use Lav45\MockServer\Parser\VariableParser;

interface ResponseFactoryInterface
{
    public function create(VariableParser $parser, array $data, RequestData $request): Response;
}
