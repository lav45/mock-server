<?php declare(strict_types=1);

namespace Lav45\MockServer\Application\Query\Request;

use Lav45\MockServer\Domain\Model\Mock;

interface Repository
{
    public function find(Request $request): Mock;
}
