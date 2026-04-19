<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory;

use Amp\Http\Server\Request;
use Lav45\MockServer\Domain\Direct;
use Lav45\MockServer\Domain\ValueObject\Body;
use Lav45\MockServer\Domain\ValueObject\HttpMethod;
use Lav45\MockServer\Parser\VariableParser;

final readonly class DirectFactory
{
    public const string TYPE = 'direct';

    public function create(Request $request, VariableParser $parser, array $data): Direct
    {
        $factory = new DataBuilder($parser, $data);
        $requestAdapter = new RequestAdapter($request);

        return new Direct(
            url: $factory->createUrl($requestAdapter->getQuery()),
            method: new HttpMethod($request->getMethod()),
            headers: $factory->createHeaders(appendHeaders: $requestAdapter->getHeaders()),
            body: Body::fromText($requestAdapter->getBody()),
        );
    }
}
