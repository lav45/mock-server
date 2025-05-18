<?php declare(strict_types=1);

namespace Lav45\MockServer;

use Amp\Http\Server\RequestHandler;

interface RequestFactoryInterface
{
    public function create(array $mock): RequestHandler;
}
