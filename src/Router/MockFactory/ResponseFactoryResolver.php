<?php declare(strict_types=1);

namespace Lav45\MockServer\Router\MockFactory;

final readonly class ResponseFactoryResolver
{
    public function __construct(
        /** @var non-empty-list<non-empty-string, ResponseFactoryInterface> */
        private array $responseFactoryList,
    ) {}

    public function resolve(array $data): ResponseFactoryInterface
    {
        $type = isset($data['response']['type'])
            ? \strtolower($data['response']['type'])
            : ContentFactory::TYPE;

        return $this->responseFactoryList[$type]
            ?? throw new \InvalidArgumentException("Unknown response type: '{$type}'");
    }
}
