<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Middleware;

use Lav45\MockServer\Domain\Model\Response\Body;
use Lav45\MockServer\Domain\Model\WebHook;
use Lav45\MockServer\Domain\Model\WebHooks;
use Lav45\MockServer\Infrastructure\Component\ArrayHelper;
use Lav45\MockServer\Infrastructure\Parser\Parser;
use Lav45\MockServer\Infrastructure\Repository\Factory\DelayFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\HeadersFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\MethodFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\UrlFactory;
use Psr\Log\LoggerInterface;

final readonly class WebHooksHandler
{
    public function __construct(
        private Parser          $parser,
        private LoggerInterface $logger,
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

        $json = ArrayHelper::getValue($item, 'options.json'); // deprecated
        if ($json === null) {
            $json = ArrayHelper::getValue($item, 'json');
        } else {
            $this->logger->info("Data:\n" . \json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            $this->logger->warning("Option 'options.json' is deprecated, you can use 'json' or run `upgrade` script.");
        }
        $json = $this->parser->replace($json);

        $headers = (new HeadersFactory(
            parser: $this->parser,
            logger: $this->logger,
            withJson: isset($json),
        ))->create(
            data: $item,
            path: 'headers',
            optionPath: 'options.headers', // deprecated
        );

        $text = ArrayHelper::getValue($item, 'options.text'); // deprecated
        if ($text === null) {
            $text = ArrayHelper::getValue($item, 'text');
        } else {
            $this->logger->info("Data:\n" . \json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            $this->logger->warning("Option 'options.text' is deprecated, you can use 'text' or run `upgrade` script.");
        }
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
