<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory;

use Lav45\MockServer\Domain\Response\ContentResponse;
use Lav45\MockServer\Parser\VariableParser;

final readonly class ContentFactory
{
    public const string TYPE = 'content';

    public function create(VariableParser $parser, array $data): ContentResponse
    {
        // TODO deprecated
        $withJson = isset($data['json'])
            || (isset($data['body']) && \is_array($data['body']));

        if ($withJson && isset($data['headers']['content-type']) === false) {
            $data['headers'] ??= [];
            $data['headers']['content-type'] = 'application/json';
        }

        $factory = new DataBuilder($parser, $data);

        return new ContentResponse(
            status: $factory->createStatus(),
            headers: $factory->createHeaders(),
            body: $factory->createBody(),
        );
    }
}
