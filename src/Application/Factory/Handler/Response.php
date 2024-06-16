<?php declare(strict_types=1);

namespace lav45\MockServer\Application\Factory\Handler;

use lav45\MockServer\Application\Handler\Response as ResponseHandler;
use lav45\MockServer\Domain\Entity\Response as ResponseEntity;

interface Response
{
    public function create(ResponseEntity $data): ResponseHandler;
}
