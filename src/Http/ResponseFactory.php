<?php declare(strict_types=1);

namespace Lav45\MockServer\Http;

use Amp\Http\Server\Response as HttpResponse;
use Lav45\MockServer\Domain\Mock\Response;

interface ResponseFactory
{
    public function create(Response $data): HttpResponse;
}
