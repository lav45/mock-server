<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Handler;

use Lav45\MockServer\Domain\Model\Response\Body;
use Lav45\MockServer\Domain\Model\WebHook;
use Lav45\MockServer\Infrastructure\Parser\Parser;

final readonly class WebHooksHandler
{
    public function handle(Parser $parser, array $data): iterable
    {
        foreach ($data['webhooks'] ?? [] as $webHook) {
            yield $this->createItem($parser, $webHook);
        }
    }

    private function createItem(Parser $parser, array $item): WebHook
    {
        $factory = new AttributeFactory($parser, $item);

        $delay = $factory->createDelay();
        $url = $factory->createUrl();
        $method = $factory->createMethod();

        $json = $item['json'] ?? null;
        $json = $parser->replace($json);

        $headers = $factory->createHeaders(isset($json));

        $text = $item['text'] ?? null;
        $text = $parser->replace($text);

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
