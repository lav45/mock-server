<?php declare(strict_types=1);

namespace lav45\MockServer;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use lav45\MockServer\Mock\Response as MockResponse;
use lav45\MockServer\RequestHandler\ContentHandler;
use lav45\MockServer\RequestHandler\DataHandler;
use lav45\MockServer\RequestHandler\ProxyHandler;
use function Amp\delay;

class RequestHandler implements \Amp\Http\Server\RequestHandler
{
    public function __construct(
        private readonly MockResponse $response,
        private readonly EnvParser    $parser,
    )
    {
    }

    public function handleRequest(Request $request): Response
    {
        if ($this->response->delay) {
            delay($this->response->delay);
        }

        $handler = match ($this->response->getType()) {
            MockResponse::TYPE_DATA => new DataHandler($this->response->getData(), $this->parser),
            MockResponse::TYPE_PROXY => new ProxyHandler($this->response->getProxy(), $this->parser),
            default => new ContentHandler($this->response->getContent(), $this->parser)
        };

        return $handler->handleRequest($request);
    }
}