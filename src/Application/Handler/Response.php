<?php declare(strict_types=1);

namespace lav45\MockServer\Application\Handler;

use lav45\MockServer\Application\DTO\Request as RequestDTO;
use lav45\MockServer\Application\DTO\Response as ResponseDTO;

interface Response
{
    public function handle(RequestDTO $request): ResponseDTO;
}