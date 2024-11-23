<?php declare(strict_types=1);

namespace Lav45\MockServer;

use FastRoute\Dispatcher;

interface WatcherInterface
{
    public function getDispatcher(): Dispatcher;
}
