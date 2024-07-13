<?php declare(strict_types=1);

namespace Lav45\MockServer\Application\Handler;

use Lav45\MockServer\Application\Data\Request as RequestData;
use Lav45\MockServer\Application\Data\Response as ResponseData;

interface Response
{
    public function handle(RequestData $request): ResponseData;
}
