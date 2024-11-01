<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Controller;

use Amp\Http\Server\Request as HttpRequest;
use Amp\Http\Server\RequestHandler as HttpRequestHandler;
use Amp\Http\Server\Response as HttpResponse;
use Faker\Generator as Faker;
use Lav45\MockServer\Application\Action\Request as RequestAction;
use Lav45\MockServer\Application\Data\Mock\v1\Mock as MockData;
use Lav45\MockServer\Application\Factory\Entity\Mock as MockFactory;
use Lav45\MockServer\Application\Factory\Entity\Response as ResponseFactory;
use Lav45\MockServer\Application\Factory\Entity\Webhooks as WebhookFactory;
use Lav45\MockServer\Infrastructure\Factory\Data\Request as RequestFactory;
use Lav45\MockServer\Infrastructure\Factory\Parser as ParserFactory;
use Lav45\MockServer\Infrastructure\Handler\Response as ResponseHandler;
use Lav45\MockServer\Infrastructure\Service\Webhook as WebhookService;
use Lav45\MockServer\Infrastructure\Wrapper\HttpClient;
use Psr\Log\LoggerInterface;

final readonly class Request implements HttpRequestHandler
{
    private ParserFactory $parser;
    private RequestAction $action;
    private MockFactory $mockFactory;

    public function __construct(
        private Faker           $faker,
        private LoggerInterface $logger,
        private HttpClient      $httpClient,
        private MockData        $mockData,
    ) {
        $this->mockFactory = new MockFactory(
            response: new ResponseFactory($this->mockData->response),
            webhooks: new WebhookFactory(...$this->mockData->webhooks),
        );

        $this->parser = new ParserFactory($this->faker, $this->mockData->env);

        $this->action = new RequestAction(
            webhookService: new WebhookService($this->logger, $this->httpClient),
            responseHandler: new ResponseHandler($this->httpClient),
        );
    }

    public function handleRequest(HttpRequest $request): HttpResponse
    {
        $requestData = RequestFactory::create($request);
        $parser = $this->parser->create($requestData);
        $mock = $this->mockFactory->create($parser);

        $responseData = $this->action->execute($requestData, $mock);

        return new HttpResponse(
            status: $responseData->status,
            headers: $responseData->headers,
            body: $responseData->body,
        );
    }
}
