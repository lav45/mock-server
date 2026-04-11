<?php declare(strict_types=1);

namespace Lav45\MockServer\Router\MockFactory;

use Lav45\MockServer\Domain\Mock\Response;
use Lav45\MockServer\Http\RequestData;
use Lav45\MockServer\Parser\VariableParser;

final readonly class ContentFactory implements ResponseFactoryInterface
{
    public const string TYPE = 'content';

    public function create(VariableParser $parser, array $data, RequestData $request): Response
    {
        $factory = new AttributeBuilder($parser, $data);

        return new Response\Content(
            delay: $factory->createDelay(),
            status: $factory->createStatus(),
            headers: $factory->createHeaders(isset($data['json'])),
            body: $factory->createBodyContent(),
        );
    }
}
