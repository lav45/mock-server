<?php declare(strict_types=1);

namespace Lav45\MockServer\Router\Watcher;

use FastRoute\Dispatcher;

interface DispatcherFactory
{
    public function create(iterable $data): Dispatcher;
}
