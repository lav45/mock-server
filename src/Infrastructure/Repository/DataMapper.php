<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository;

use Lav45\MockServer\Application\Query\Request\Request;
use Lav45\MockServer\Domain\Model\Mock;
use Lav45\MockServer\Infrastructure\Parser\Parser;
use Lav45\MockServer\Infrastructure\Repository\Middleware\CollectionMiddleware;
use Lav45\MockServer\Infrastructure\Repository\Middleware\ContentMiddleware;
use Lav45\MockServer\Infrastructure\Repository\Middleware\MiddlewarePipeline;
use Lav45\MockServer\Infrastructure\Repository\Middleware\ProxyMiddleware;
use Lav45\MockServer\Infrastructure\Repository\Middleware\WebHooksHandler;

final readonly class DataMapper
{
    public function __construct(
        private Parser $parser,
    ) {}

    public function toModel(array $data, Request $request): Mock
    {
        $webHooks = (new WebHooksHandler($this->parser))->handle($data);

        $response = (new MiddlewarePipeline(
            new ProxyMiddleware($this->parser),
            new CollectionMiddleware($this->parser),
            new ContentMiddleware($this->parser),
        ))->handle($data, $request);

        return new Mock(
            response: $response,
            webHooks: $webHooks,
        );
    }
}
