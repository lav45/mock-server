<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Middleware;

use Lav45\MockServer\Application\Query\Request\Request;
use Lav45\MockServer\Domain\Model\Response;
use Lav45\MockServer\Infrastructure\Component\ArrayHelper;
use Lav45\MockServer\Infrastructure\Parser\Parser;
use Lav45\MockServer\Infrastructure\Repository\Factory\BodyFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\DelayFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\HeadersFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\StatusFactory;

final readonly class ContentMiddleware implements Middleware
{
    public function __construct(
        private Parser $parser,
    ) {}

    public function handle(array $data, Request $request, \Closure $next): Response
    {
        $response = ArrayHelper::getValue($data, 'response', []);
        return $this->createResponseContent($response, $request);
    }

    private function createResponseContent(array $data, Request $request): Response
    {
        $start = new Response\Start($request->start);

        $delay = (new DelayFactory($this->parser))->create($data, 'delay');

        $status = (new StatusFactory($this->parser))->create($data, 'content.status');

        $headers = (new HeadersFactory(
            parser: $this->parser,
            withJson: isset($data['content']['json']),
        ))->create(
            data: $data,
            path: 'content.headers',
        );

        $body = (new BodyFactory($this->parser))->fromContent($data, 'content.text', 'content.json');

        return new Response\Content(
            start: $start,
            delay: $delay,
            status: $status,
            headers: $headers,
            body: $body,
        );
    }
}
