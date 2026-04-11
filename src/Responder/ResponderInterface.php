<?php declare(strict_types=1);

namespace Lav45\MockServer\Responder;

use Lav45\MockServer\Domain\Mock\Response;
use Lav45\MockServer\Http\ResponseData;

interface ResponderInterface
{
    public function execute(Response $data): ResponseData;
}
