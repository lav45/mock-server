<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory;

use Lav45\MockServer\Domain\WebHooks;
use Lav45\MockServer\Domain\WebHooks\WebHook;
use Lav45\MockServer\Parser\VariableParser;

final readonly class WebHooksFactory
{
    public function create(VariableParser $parser, array $data): WebHooks
    {
        $items = [];
        foreach ($data as $webHook) {
            $items[] = $this->createItem($parser, $webHook);
        }
        return new WebHooks(...$items);
    }

    private function createItem(VariableParser $parser, array $item): WebHook
    {
        // TODO deprecated
        $withJson = isset($item['json'])
            || (isset($item['body']) && \is_array($item['body']));

        if ($withJson && isset($item['headers']['content-type']) === false) {
            $item['headers'] ??= [];
            $item['headers']['content-type'] = 'application/json';
        }

        $factory = new DataBuilder($parser, $item);

        $delay = $factory->createDelay();
        $url = $factory->createUrl();
        $method = $factory->createMethod();

        $headers = $factory->createHeaders();
        $body = $factory->createBody();

        return new WebHook(
            delay: $delay,
            url: $url,
            method: $method,
            headers: $headers,
            body: $body,
        );
    }
}
