<?php declare(strict_types=1);

namespace Lav45\MockServer\Router;

use Lav45\MockServer\Domain\Mock;
use Lav45\MockServer\Http\RequestData;

interface MockFactoryInterface
{
    public function create(RequestData $request, array $data): Mock;
}
