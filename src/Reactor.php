<?php declare(strict_types=1);

namespace Lav45\MockServer;

use Amp\Http\HttpStatus;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler as RequestHandlerInterface;
use Amp\Http\Server\Response;
use FastRoute\Dispatcher;
use Lav45\MockServer\Infrastructure\Wrapper\Request as RequestWrapper;

final readonly class Reactor implements RequestHandlerInterface
{
    public function __construct(
        private ErrorHandler $errorHandler,
        private Watcher      $watcher,
    ) {}

    public function handleRequest(Request $request): Response
    {
        $match = $this->watcher->getDispatcher()->dispatch(
            $request->getMethod(),
            $request->getUri()->getPath(),
        );

        return match ($match[0]) {
            Dispatcher::FOUND => $this->makeFoundResponse($match[1], $match[2], $request),
            Dispatcher::NOT_FOUND => $this->makeNotFoundResponse($request),
            Dispatcher::METHOD_NOT_ALLOWED => $this->makeMethodNotAllowedResponse($match[1], $request),
        };
    }

    private function makeFoundResponse(RequestHandlerInterface $handler, array $requestArgs, Request $request): Response
    {
        $request->setAttribute(RequestWrapper::URL_PARAMS, $requestArgs);

        return $handler->handleRequest($request);
    }

    /**
     * Create a response if no routes matched and no fallback has been set.
     */
    private function makeNotFoundResponse(Request $request): Response
    {
        return $this->errorHandler->handleError(HttpStatus::NOT_FOUND, null, $request);
    }

    /**
     * Create a response if the requested method is not allowed for the matched path.
     */
    private function makeMethodNotAllowedResponse(array $methods, Request $request): Response
    {
        $response = $this->errorHandler->handleError(HttpStatus::METHOD_NOT_ALLOWED, null, $request);
        $response->setHeader('allow', \implode(', ', $methods));
        return $response;
    }
}
