<?php declare(strict_types=1);

namespace Lav45\MockServer\Application\Factory\Handler;

use Lav45\MockServer\Application\Handler\Response as ResponseHandler;
use Lav45\MockServer\Domain\Entity\Response as ResponseEntity;

interface Response
{
    public function create(ResponseEntity $data): ResponseHandler;
}
