<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Handler;

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

final readonly class ResponseProxyHandler implements Handler
{
    public const string TYPE = 'proxy';

    public function __construct(
        private Parser $parser,
    ) {}

    public function handle(array $data, Request $request): Response
    {
        $data = ArrayHelper::getValue($data, 'response', []);

        $start = new Response\Start($request->start);

        $delay = (new DelayFactory($this->parser))->create($data, 'delay');

        $url = (new UrlFactory($this->parser))->create($data, 'url', $request->get);

        $method = new HttpMethod($request->method);

        $withJson = isset($data['content']) && \is_array($data['content']);

        $headers = (new HeadersFactory(
            parser: $this->parser,
            withJson: $withJson,
            appendHeaders: $request->headers,
        ))->create(
            data: $data,
            path: 'headers',
        );

        if (isset($data['content'])) {
            $body = (new BodyFactory($this->parser))->from($data, 'content');
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
