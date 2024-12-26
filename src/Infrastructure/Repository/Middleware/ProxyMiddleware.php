<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Middleware;

use Closure;
use Lav45\MockServer\Application\Query\Request\Request;
use Lav45\MockServer\Domain\Model\Response;
use Lav45\MockServer\Domain\Model\Response\Body;
use Lav45\MockServer\Domain\Model\Response\HttpMethod;
use Lav45\MockServer\Infrastructure\Component\ArrayHelper;
use Lav45\MockServer\Infrastructure\Parser\Parser;
use Lav45\MockServer\Infrastructure\Repository\Factory\BodyFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\DelayFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\HeadersFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\UrlFactory;

final readonly class ProxyMiddleware implements Middleware
{
    public function __construct(
        private Parser $parser,
    ) {}

    public function handle(array $data, Request $request, Closure $next): Response
    {
        $response = ArrayHelper::getValue($data, 'response', []);
        if (isset($response['proxy'])) {
            return $this->createResponseProxy($response, $request);
        }
        return $next($data, $request);
    }

    private function createResponseProxy(array $data, Request $request): Response
    {
        $start = new Response\Start($request->start);

        $delay = (new DelayFactory($this->parser))->create($data, 'delay');

        $url = (new UrlFactory($this->parser))->create($data, 'proxy.url', $request->get);

        $method = new HttpMethod($request->method);

        $withJson = isset($data['proxy']['content']) && \is_array($data['proxy']['content']);

        $headers = (new HeadersFactory(
            parser: $this->parser,
            withJson: $withJson,
            appendHeaders: $request->headers,
        ))->create(
            data: $data,
            path: 'proxy.headers',
            optionPath: 'proxy.options.headers',
        );

        if (isset($data['proxy']['content'])) {
            $body = (new BodyFactory($this->parser))->from($data, 'proxy.content');
        } else {
            $body = Body::fromText($request->body);
        }

        return new Response\Proxy(
            start: $start,
            delay: $delay,
            url: $url,
            method: $method,
            headers: $headers,
            body: $body,
        );
    }
}
