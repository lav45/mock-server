<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Handler;

use Lav45\MockServer\Domain\Model\Response\Body;
use Lav45\MockServer\Domain\Model\WebHook;
use Lav45\MockServer\Domain\Model\WebHooks;
use Lav45\MockServer\Infrastructure\Component\ArrayHelper;
use Lav45\MockServer\Infrastructure\Parser\Parser;
use Lav45\MockServer\Infrastructure\Repository\Factory\DelayFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\HeadersFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\MethodFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\UrlFactory;

final readonly class WebHooksHandler
{
    public function __construct(
        private Parser $parser,
    ) {}

    public function handle(array $data): WebHooks
    {
        $items = [];
        $webHooks = ArrayHelper::getValue($data, 'webhooks', []);
        foreach ($webHooks as $webHook) {
            $items[] = $this->createItem($webHook);
        }
        return new WebHooks(...$items);
    }

    private function createItem(array $item): WebHook
    {
        $delay = (new DelayFactory($this->parser))->create($item, 'delay');

        $url = (new UrlFactory($this->parser))->create($item, 'url');

        $method = (new MethodFactory($this->parser))->create($item, 'method', 'POST');

        $json = ArrayHelper::getValue($item, 'json');
        $json = $this->parser->replace($json);

        $headers = (new HeadersFactory(
            parser: $this->parser,
            withJson: isset($json),
        ))->create(
            data: $item,
            path: 'headers',
        );

        $text = ArrayHelper::getValue($item, 'text');
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
