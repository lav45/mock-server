<?php declare(strict_types=1);

namespace Lav45\MockServer\Http;

use Amp\Http\Server\Request;

interface RequestDataFactoryInterface
{
    public function create(Request $request, array $urlParams = []): RequestData;
}
