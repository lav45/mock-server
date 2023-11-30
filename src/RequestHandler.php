<?php declare(strict_types=1);

namespace lav45\MockServer;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use lav45\MockServer\Mock\Response as MockResponse;
use lav45\MockServer\RequestHandler\ContentHandler;
use lav45\MockServer\RequestHandler\DataHandler;
use lav45\MockServer\RequestHandler\ProxyHandler;
use Monolog\Logger;
use function Amp\delay;

class RequestHandler implements \Amp\Http\Server\RequestHandler
{
    public function __construct(
        private readonly MockResponse $response,
        private readonly EnvParser    $parser,
        private readonly Logger       $logger,
        private readonly HttpClient   $httpClient,
    )
    {
    }

    public function handleRequest(Request $request): Response
    {
        if ($this->response->delay) {
            delay($this->response->delay);
        }

        $type = $this->response->getType() ?: MockResponse::TYPE_CONTENT;
        $handler = match ($type) {
            MockResponse::TYPE_DATA => new DataHandler($this->response->getData(), $this->parser),
            MockResponse::TYPE_PROXY => new ProxyHandler($this->response->getProxy(), $this->parser, $this->httpClient),
            MockResponse::TYPE_CONTENT => new ContentHandler($this->response->getContent(), $this->parser)
        };

        $response = $handler->handleRequest($request);

        $url = $request->getUri();
        $method = $request->getMethod();
        $statusCode = $response->getStatus();
        $message = "Request {$type}: {$statusCode} {$method} {$url}";
        ($statusCode === 200) ?
            $this->logger->info($message) :
            $this->logger->warning($message);

        return $response;
    }
}