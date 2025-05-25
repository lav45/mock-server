<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Handler;

use Lav45\MockServer\Domain\Model\Response\Body;
use Lav45\MockServer\Domain\Model\WebHook;
use Lav45\MockServer\Domain\Model\WebHooks;
use Lav45\MockServer\Infrastructure\Parser\Parser;

final readonly class WebHooksHandler
{
    public function __construct(
        private Parser $parser,
    ) {}

    public function handle(array $data): WebHooks
    {
        $items = [];
        $webHooks = $data['webhooks'] ?? [];
        foreach ($webHooks as $webHook) {
            $items[] = $this->createItem($webHook);
        }
        return new WebHooks(...$items);
    }

    private function createItem(array $item): WebHook
    {
        $factory = new AttributeFactory($this->parser, $item);

        $delay = $factory->createDelay();
        $url = $factory->createUrl();
        $method = $factory->createMethod();

        $json = $item['json'] ?? null;
        $json = $this->parser->replace($json);

        $headers = $factory->createHeaders(isset($json));

        $text = $item['text'] ?? null;
        $text = $this->parser->replace($text);

        if ($json) {
            $body = Body::fromJson($json);
        } else {
            $body = Body::fromText($text);
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
