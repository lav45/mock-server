<?php declare(strict_types=1);

namespace Lav45\MockServer\Responder;

use Lav45\MockServer\Domain\Mock\Response;

interface ResponderFactoryInterface
{
    public function create(Response $data): ResponderInterface;
}
