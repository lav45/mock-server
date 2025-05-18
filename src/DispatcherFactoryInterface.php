<?php declare(strict_types=1);

namespace Lav45\MockServer;

use FastRoute\Dispatcher;

interface DispatcherFactoryInterface
{
    public function create(iterable $data): Dispatcher;
}
