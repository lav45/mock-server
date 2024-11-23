<?php declare(strict_types=1);

namespace Lav45\MockServer;

use Lav45\MockServer\Presenter\Handler\Request;

interface RequestFactoryInterface
{
    public function create(array $mock): Request;
}
