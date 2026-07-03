<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Content;

use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\Domain\Response\ContentResponse;

final readonly class ContentFactory
{
    private const string TYPE = 'content';

    public function __construct(
        private DataBuilder $dataBuilder,
    ) {}

    /**
     * Content is the default response type, so a missing type is treated as a match.
     */
    public function has(array $data): bool
    {
        return empty($data['type']) || $data['type'] === self::TYPE;
    }

    public function create(array $data): ContentResponse
    {
        $factory = $this->dataBuilder->withData($data);

        $body = $factory->createBody();
        $appendHeaders = $body->isJson() ? ['content-type' => 'application/json'] : [];
        $headers = $factory->createHeaders($appendHeaders);

        return new ContentResponse(
            status: $factory->createStatus(),
            headers: $headers,
            body: $body,
        );
    }
}
