<?php declare(strict_types=1);

namespace lav45\MockServer\Application\Handler;

use lav45\MockServer\Application\Data\Request as RequestData;
use lav45\MockServer\Application\Data\Response as ResponseData;

interface Response
{
    public function handle(RequestData $request): ResponseData;
}
