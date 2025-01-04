<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Handler;

use Lav45\MockServer\Application\Query\Request\Request;
use Lav45\MockServer\Domain\Model\Response;
use Lav45\MockServer\Infrastructure\Component\ArrayHelper;
use Lav45\MockServer\Infrastructure\Parser\Parser;
use Lav45\MockServer\Infrastructure\Repository\Factory\BodyFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\DelayFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\HeadersFactory;
use Lav45\MockServer\Infrastructure\Repository\Factory\StatusFactory;

final readonly class ResponseContentHandler implements Handler
{
    public const string TYPE = 'content';

    public function __construct(
        private Parser $parser,
    ) {}

    public function handle(array $data, Request $request): Response
    {
        $data = ArrayHelper::getValue($data, 'response', []);

        $start = new Response\Start($request->start);

        $delay = (new DelayFactory($this->parser))->create($data, 'delay');

        $status = (new StatusFactory($this->parser))->create($data, 'status');

        $headers = (new HeadersFactory(
            parser: $this->parser,
            withJson: isset($data['json']),
        ))->create(
            data: $data,
            path: 'headers',
        );

        $body = (new BodyFactory($this->parser))->fromContent($data, 'text', 'json');

        return new Response\Content(
            start: $start,
            delay: $delay,
            status: $status,
            headers: $headers,
            body: $body,
        );
    }
}
