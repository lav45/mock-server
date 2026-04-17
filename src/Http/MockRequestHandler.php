<?php declare(strict_types=1);

namespace Lav45\MockServer\Http;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;

final class MockRequestHandler implements RequestHandler, RequestFactory
{
    private array $data;

    public function __construct(
        private readonly WebHookHandler  $webHookHandler,
        private readonly ResponseFactory $responseFactory,
        private readonly MockFactory     $mockFactory,
        private readonly DelayHandler    $delayHandler,
    ) {}

    public function withData(array $data): self
    {
        $new = clone $this;
        $new->data = $data;
        return $new;
    }

    public function handleRequest(Request $request): Response
    {
        $mock = $this->mockFactory->create($request, $this->data);

        $delay = $this->delayHandler->start();

        $response = $this->responseFactory->create($mock->response);

        $this->webHookHandler->send($mock->webHooks);

        $delay->wait($mock->response->delay());

        return $response;
    }
}
