<?php declare(strict_types=1);

namespace Lav45\MockServer\Driver;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Request as AmpRequest;
use Amp\Http\Server\Response as AmpResponse;
use Lav45\MockServer\Engine\Http\RequestHandler as EngineRequestHandler;

final readonly class RequestHandler implements \Amp\Http\Server\RequestHandler
{
    public function __construct(
        private EngineRequestHandler $handler,
        private int                  $maxRequestBodySize = \PHP_INT_MAX,
        private ErrorHandler         $errorHandler = new ErrorHandler(),
    ) {}

    public function handleRequest(AmpRequest $request): AmpResponse
    {
        $serverRequest = new ServerRequest($request, $this->maxRequestBodySize);

        try {
            $this->assertBodySizeWithinLimit($request);
            $response = $this->handler->handleRequest($serverRequest);
        } catch (RequestBodyTooLargeException) {
            $request->getBody()->close();
            return $this->errorHandler->handleError(HttpStatus::PAYLOAD_TOO_LARGE, request: $request);
        }

        $bodyStream = $response->getBody()->stream;
        $body = $bodyStream instanceof AmpStream
            ? $bodyStream->getStream()
            : $bodyStream->read();

        $ampResponse = new AmpResponse(
            headers: $response->getHeaders(),
            body: $body,
        );
        $ampResponse->setStatus($response->getStatus(), $response->getReason());

        $serverRequest->drainBody();

        return $ampResponse;
    }

    private function assertBodySizeWithinLimit(AmpRequest $request): void
    {
        $contentLength = $request->getHeader('content-length');
        if ($contentLength !== null && (int)$contentLength > $this->maxRequestBodySize) {
            throw new RequestBodyTooLargeException(
                "Request body exceeds the limit of {$this->maxRequestBodySize} bytes",
            );
        }
    }
}
