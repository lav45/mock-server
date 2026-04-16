<?php declare(strict_types=1);

namespace Lav45\MockServer\Responder;

use Amp\Http\Server\Response as HttpResponse;
use Lav45\MockServer\Domain\Mock\Response;

interface ResponseFactoryInterface
{
    public function create(Response $data): HttpResponse;
}
