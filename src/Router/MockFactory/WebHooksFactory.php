<?php declare(strict_types=1);

namespace Lav45\MockServer\Router\MockFactory;

use Lav45\MockServer\Domain\Mock\Response\Body;
use Lav45\MockServer\Domain\Mock\WebHooks;
use Lav45\MockServer\Domain\Mock\WebHooks\WebHook;
use Lav45\MockServer\Parser\VariableParser;

final readonly class WebHooksFactory implements WebHooksFactoryInterface
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
        $factory = new AttributeBuilder($parser, $item);

        $delay = $factory->createDelay();
        $url = $factory->createUrl();
        $method = $factory->createMethod();

        $isJson = isset($item['json']);
        $headers = $factory->createHeaders($isJson);

        if ($isJson) {
            $body = Body::fromJson(
                $parser->replace($item['json']),
            );
        } else {
            $body = Body::fromText(
                $parser->replace($item['text'] ?? ''),
            );
        }

        return new WebHook(
            delay: $delay,
            url: $url,
            method: $method,
            headers: $headers,
            body: $body,
        );
    }
}
