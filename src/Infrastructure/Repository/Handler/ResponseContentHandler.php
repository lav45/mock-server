<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Handler;

use Lav45\MockServer\Application\Query\Request\Request;
use Lav45\MockServer\Domain\Model\Response;
use Lav45\MockServer\Infrastructure\Parser\Parser;

final readonly class ResponseContentHandler implements Handler
{
    public const string TYPE = 'content';

    public function __construct(
        private Parser $parser,
    ) {}

    public function handle(array $data, Request $request): Response
    {
        $data = $data['response'] ?? [];

        $factory = new AttributeFactory($this->parser, $data);

        $start = new Response\Start($request->start);
        $delay = $factory->createDelay();
        $status = $factory->createStatus();
        $headers = $factory->createHeaders(isset($data['json']));
        $body = $factory->createBodyContent();

        return new Response\Content(
            start: $start,
            delay: $delay,
            status: $status,
            headers: $headers,
            body: $body,
        );
    }
}
