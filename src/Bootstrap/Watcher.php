<?php declare(strict_types=1);

namespace Lav45\MockServer\Bootstrap;

use FastRoute\Dispatcher;

interface Watcher
{
    public function getDispatcher(): Dispatcher;
}
