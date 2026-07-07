<?php declare(strict_types=1);

namespace Lav45\MockServer\Driver;

use Amp\Http\Server\Request as AmpRequest;
use Amp\Http\Server\Response as AmpResponse;
use Lav45\MockServer\Engine\Http\RequestHandler as EngineRequestHandler;

final readonly class RequestHandler implements \Amp\Http\Server\RequestHandler
{
    public function __construct(
        private EngineRequestHandler $handler,
    ) {}

    public function handleRequest(AmpRequest $request): AmpResponse
    {
        $response = $this->handler->handleRequest(new ServerRequest($request));

        $bodyStream = $response->getBody()->stream;
        $body = $bodyStream instanceof AmpStream
            ? $bodyStream->getStream()
            : $bodyStream->read();

        $ampResponse = new AmpResponse(
            headers: $response->getHeaders(),
            body: $body,
        );
        $ampResponse->setStatus($response->getStatus(), $response->getReason());

        return $ampResponse;
    }
}
