<?php declare(strict_types=1);

namespace Lav45\MockServer\Application\Query\Request;

use Lav45\MockServer\Domain\Model\Response as ResponseModel;

interface ResponseFabric
{
    public function create(ResponseModel $data): ResponseHandler;
}
