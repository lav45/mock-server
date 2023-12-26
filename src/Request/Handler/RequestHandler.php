<?php declare(strict_types=1);

namespace lav45\MockServer\Request\Handler;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use lav45\MockServer\EnvParser;
use lav45\MockServer\FakerParser;
use lav45\MockServer\HttpClient;
use lav45\MockServer\Mock\Response as MockResponse;
use lav45\MockServer\Mock\Webhook;
use lav45\MockServer\Request\Wrapper\RequestWrapper;
use Psr\Log\LoggerInterface;
use function Amp\delay;

readonly class RequestHandler implements \Amp\Http\Server\RequestHandler
{
    /**
     * @param Webhook[] $webhooks
     */
    public function __construct(
        private MockResponse    $response,
        private array           $webhooks,
        private FakerParser     $faker,
        private array           $env,
        private LoggerInterface $logger,
        private HttpClient      $httpClient,
    )
    {
    }

    public function handleRequest(Request $request): Response
    {
        $requestWrapper = new RequestWrapper($request);
        $parser = $this->createParser($requestWrapper, $this->faker, $this->env);

        $this->sendWebhook($this->logger, $parser, $this->httpClient, $this->webhooks);

        $type = $this->response->getType(MockResponse::TYPE_CONTENT);
        $delay = $this->response->delay;

        return $this->runRequest($this->logger, $parser, $this->httpClient, $requestWrapper, $type, $delay);
    }

    protected function runRequest(LoggerInterface $logger, EnvParser $parser, HttpClient $httpClient, RequestWrapper $request, string $type, float $delay = 0): Response
    {
        $handler = $this->createResponseHandler($type, $parser, $httpClient);

        $delay && delay($delay);

        $response = $handler->handleRequest($request);

        $this->logRequest($logger, $request, $type, $response->getStatus());

        return $response;
    }

    private function logRequest(LoggerInterface $logger, RequestWrapper $request, string $type, int $statusCode): void
    {
        $url = $request->getUri();
        $method = $request->getMethod();
        $message = "Request {$type}: {$statusCode} {$method} {$url}";
        ($statusCode === 200) ?
            $logger->info($message) :
            $logger->warning($message);
    }

    private function createResponseHandler(string $type, EnvParser $parser, HttpClient $httpClient): RequestHandlerInterface
    {
        return match ($type) {
            MockResponse::TYPE_DATA => new DataHandler($this->response->getData(), $parser),
            MockResponse::TYPE_PROXY => new ProxyHandler($this->response->getProxy(), $parser, $httpClient),
            MockResponse::TYPE_CONTENT => new ContentHandler($this->response->getContent(), $parser)
        };
    }

    protected function sendWebhook(LoggerInterface $logger, EnvParser $parser, HttpClient $httpClient, array $webhooks): void
    {
        (new WebhookHandler($logger, $parser, $httpClient))->send($webhooks);
    }

    private function createParser(RequestWrapper $requestWrapper, $faker, $env): EnvParser
    {
        $parser = new EnvParser($faker);
        $parser->addData([
            'env' => $parser->replaceFaker($env),
            'request' => [
                'get' => $requestWrapper->get(),
                'post' => $requestWrapper->post(),
                'urlParams' => $requestWrapper->getUrlParams()
            ]
        ]);
        return $parser;
    }
}