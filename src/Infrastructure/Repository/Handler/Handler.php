<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Handler;

use Lav45\MockServer\Application\Query\Request\Request;
use Lav45\MockServer\Domain\Model\Response;
use Lav45\MockServer\Infrastructure\Parser\DataParser;

interface Handler
{
    public function handle(DataParser $parser, array $data, Request $request): Response;
}
