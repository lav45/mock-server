<?php declare(strict_types=1);

namespace Lav45\MockServer\Http;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Lav45\MockServer\Responder\DelayHandlerInterface;
use Lav45\MockServer\Responder\ResponseFactoryInterface;
use Lav45\MockServer\Responder\WebHookHandlerInterface;
use Lav45\MockServer\Router\MockFactoryInterface;

final class MockRequestHandler implements RequestHandler, RequestFactory
{
    private array $data;

    public function __construct(
        private readonly WebHookHandlerInterface  $webHookHandler,
        private readonly ResponseFactoryInterface $responseFabric,
        private readonly MockFactoryInterface     $mockFactory,
        private readonly DelayHandlerInterface    $delayHandler,
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

        $response = $this->responseFabric->create($mock->response);

        $this->webHookHandler->send($mock->webHooks);

        $delay->wait($mock->response->delay());

        return $response;
    }
}
