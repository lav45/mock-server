<?php declare(strict_types=1);

namespace lav45\MockServer\Application\Factory\Entity;

use lav45\MockServer\Application\DTO\Mock\v1\Webhook as WebhookDTO;
use lav45\MockServer\Domain\Factory\Response\HttpHeaders as HttpHeadersFactory;
use lav45\MockServer\Domain\Service\Parser;
use lav45\MockServer\Domain\ValueObject\Response\Body;
use lav45\MockServer\Domain\ValueObject\Response\Delay;
use lav45\MockServer\Domain\ValueObject\Response\HttpMethod;
use lav45\MockServer\Domain\ValueObject\Response\Url;
use lav45\MockServer\Domain\ValueObject\Webhook;

final readonly class Webhooks
{
    private array $data;

    public function __construct(WebhookDTO ...$data)
    {
        $this->data = $data;
    }

    public function create(Parser $parser): iterable
    {
        foreach ($this->data as $item) {
            yield $this->createWebhookItem($item, $parser);
        }
    }

    private function createWebhookItem(WebhookDTO $item, Parser $parser): Webhook
    {
        $delay = Delay::new($parser->replace($item->delay));
        $url = new Url($parser->replace($item->url));
        $method = HttpMethod::new($parser->replace($item->method));
        $headers = HttpHeadersFactory::new($parser, $item->headers, isset($item->json));

        $body = Body::from(
            json: $parser->replace($item->json),
            text: $parser->replace($item->text)
        );

        return new Webhook(
            delay: $delay,
            url: $url,
            method: $method,
            headers: $headers,
            body: $body,
        );
    }
}