<?php declare(strict_types=1);

namespace Lav45\MockServer\Router;

use Amp\Http\Server\Request;
use Lav45\MockServer\Domain\Mock;

interface MockFactoryInterface
{
    public function create(Request $request, array $data): Mock;
}
