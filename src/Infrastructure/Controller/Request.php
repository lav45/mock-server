<?php declare(strict_types=1);

namespace lav45\MockServer\Infrastructure\Controller;

use Amp\Http\Server\Request as HttpRequest;
use Amp\Http\Server\RequestHandler as HttpRequestHandler;
use Amp\Http\Server\Response as HttpResponse;
use Faker\Generator;
use lav45\MockServer\Application\Action\Request as RequestAction;
use lav45\MockServer\Application\DTO\Mock\v1\Mock as MockDTO;
use lav45\MockServer\Application\Factory\Entity\Mock as MockFactory;
use lav45\MockServer\Application\Factory\Entity\Response as ResponseFactory;
use lav45\MockServer\Application\Factory\Entity\Webhooks as WebhookFactory;
use lav45\MockServer\Infrastructure\Factory\Parser as ParserFactory;
use lav45\MockServer\Infrastructure\Factory\Request as RequestFactory;
use lav45\MockServer\Infrastructure\Handler\Response as ResponseHandler;
use lav45\MockServer\Infrastructure\Service\Webhook as WebhookService;
use lav45\MockServer\Infrastructure\Wrapper\HttpClient;
use Psr\Log\LoggerInterface;

final readonly class Request implements HttpRequestHandler
{
    private ParserFactory $parser;
    private RequestAction $action;
    private MockFactory $mockFactory;

    public function __construct(
        private Generator       $faker,
        private LoggerInterface $logger,
        private HttpClient      $httpClient,
        private MockDTO         $mockDto,
    ) {
        $this->mockFactory = new MockFactory(
            response: new ResponseFactory($this->mockDto->response),
            webhooks: new WebhookFactory(...$this->mockDto->webhooks),
        );

        $this->parser = new ParserFactory($this->faker, $this->mockDto->env);

        $this->action = new RequestAction(
            webhookService: new WebhookService($this->logger, $this->httpClient),
            responseHandler: new ResponseHandler($this->httpClient),
        );
    }

    public function handleRequest(HttpRequest $request): HttpResponse
    {
        $requestDto = RequestFactory::create($request);
        $parser = $this->parser->create($requestDto);
        $mock = $this->mockFactory->create($parser);

        $responseDto = $this->action->execute($requestDto, $mock);

        return new HttpResponse(
            status: $responseDto->status,
            headers: $responseDto->headers,
            body: $responseDto->body,
        );
    }
}
