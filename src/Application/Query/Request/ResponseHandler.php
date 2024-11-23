<?php declare(strict_types=1);

namespace Lav45\MockServer\Application\Query\Request;

interface ResponseHandler
{
    public function execute(): Response;
}
