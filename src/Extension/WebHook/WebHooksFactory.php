<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\WebHook;

use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\Domain\WebHook;
use Lav45\MockServer\Domain\WebHooks;

final readonly class WebHooksFactory
{
    private const string TYPE = 'webhooks';

    public function __construct(
        private DataBuilder $dataBuilder,
    ) {}

    public function has(array $data): bool
    {
        return isset($data[self::TYPE]) && $data[self::TYPE];
    }

    public function create(array $data): WebHooks
    {
        $items = [];
        foreach ($data[self::TYPE] as $webHook) {
            $items[] = $this->createItem($webHook);
        }
        return new WebHooks(...$items);
    }

    private function createItem(array $item): WebHook
    {
        $factory = $this->dataBuilder->withData($item);

        $body = $factory->createBody();
        $headers = $factory->createHeaders();

        return new WebHook(
            delay: $factory->createDelay(),
            url: $factory->createUrl(),
            method: $factory->createMethod(),
            headers: $headers,
            body: $body,
        );
    }
}
