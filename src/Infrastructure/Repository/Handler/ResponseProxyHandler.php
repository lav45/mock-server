<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Handler;

use Lav45\MockServer\Application\Query\Request\Request;
use Lav45\MockServer\Domain\Model\Response;
use Lav45\MockServer\Domain\Model\Response\Body;
use Lav45\MockServer\Domain\Model\Response\HttpMethod;
use Lav45\MockServer\Infrastructure\Parser\Parser;

final readonly class ResponseProxyHandler implements Handler
{
    public const string TYPE = 'proxy';

    public function __construct(
        private Parser $parser,
    ) {}

    public function handle(array $data, Request $request): Response
    {
        $data = $data['response'] ?? [];

        $factory = new AttributeFactory($this->parser, $data);

        $start = new Response\Start($request->start);
        $delay = $factory->createDelay();
        $url = $factory->createUrl($request->get);
        $method = new HttpMethod($request->method);

        $withJson = isset($data['content']) && \is_array($data['content']);
        $headers = $factory->createHeaders($withJson, $request->headers);

        if (isset($data['content'])) {
            $body = $factory->createBody();
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
