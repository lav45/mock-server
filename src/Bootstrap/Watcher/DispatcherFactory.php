<?php declare(strict_types=1);

namespace Lav45\MockServer\Bootstrap\Watcher;

use FastRoute\Dispatcher;

interface DispatcherFactory
{
    public function create(array $data): Dispatcher;
}
