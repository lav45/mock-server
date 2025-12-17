<?php declare(strict_types=1);

namespace Lav45\MockServer\Watcher;

use FastRoute\Dispatcher;

interface DispatcherFactory
{
    public function create(iterable $data): Dispatcher;
}
