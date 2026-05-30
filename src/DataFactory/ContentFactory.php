<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory;

use Lav45\MockServer\Domain\Response\ContentResponse;
use Lav45\MockServer\Parser\VariableParser;

final readonly class ContentFactory
{
    public const string TYPE = 'content';

    public function create(VariableParser $parser, array $data): ContentResponse
    {
        $factory = new DataBuilder($parser, $data);

        $isJson = (isset($data['json']) && \is_array($data['json'])) // TODO deprecated
            || (isset($data['body']) && \is_array($data['body']));

        return new ContentResponse(
            status: $factory->createStatus(),
            headers: $factory->createHeaders($isJson),
            body: $factory->createBody(),
        );
    }
}
