<?php declare(strict_types=1);

namespace Lav45\MockServer\Driver;

use Amp\Http\Server\Request as AmpRequest;
use Amp\Http\Server\RequestHandler as AmpRequestHandlerInterface;
use Amp\Http\Server\Response as AmpResponse;
use Lav45\MockServer\Engine\Http\RequestHandler;

final readonly class AmpRequestHandler implements AmpRequestHandlerInterface
{
    public function __construct(
        private RequestHandler $handler,
    ) {}

    public function handleRequest(AmpRequest $request): AmpResponse
    {
        $response = $this->handler->handleRequest(new ServerRequest($request));

        $ampResponse = new AmpResponse(
            headers: $response->getHeaders(),
            body: $response->getBody(),
        );
        $ampResponse->setStatus($response->getStatus(), $response->getReason());

        return $ampResponse;
    }
}
