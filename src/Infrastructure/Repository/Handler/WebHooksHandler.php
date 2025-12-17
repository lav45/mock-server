<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Handler;

use Lav45\MockServer\Domain\Model\Response\Body;
use Lav45\MockServer\Domain\Model\WebHook;
use Lav45\MockServer\Infrastructure\Parser\DataParser;

final readonly class WebHooksHandler
{
    public function handle(DataParser $parser, array $data): iterable
    {
        foreach ($data as $webHook) {
            yield $this->createItem($parser, $webHook);
        }
    }

    private function createItem(DataParser $parser, array $item): WebHook
    {
        $factory = new AttributeFactory($parser, $item);

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
