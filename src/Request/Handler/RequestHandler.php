<?php declare(strict_types=1);

namespace lav45\MockServer\Request\Handler;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use lav45\MockServer\EnvParser;
use lav45\MockServer\HttpClient;
use lav45\MockServer\Mock\Response as MockResponse;
use Monolog\Logger;
use function Amp\delay;

readonly class RequestHandler implements \Amp\Http\Server\RequestHandler
{
    public function __construct(
        private MockResponse $response,
        private Logger       $logger,
        private HttpClient   $httpClient,
    )
    {
    }

    public function handleRequest(Request $request): Response
    {
        if ($this->response->delay) {
            delay($this->response->delay);
        }

        $parser = $request->getAttribute(EnvParser::class);

        $type = $this->response->getType(MockResponse::TYPE_CONTENT);
        $handler = match ($type) {
            MockResponse::TYPE_DATA => new DataHandler($this->response->getData(), $parser),
            MockResponse::TYPE_PROXY => new ProxyHandler($this->response->getProxy(), $parser, $this->httpClient),
            MockResponse::TYPE_CONTENT => new ContentHandler($this->response->getContent(), $parser)
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