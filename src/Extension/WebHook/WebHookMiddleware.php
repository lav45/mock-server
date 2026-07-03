<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\WebHook;

use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Engine\WebHookQueue;
use Lav45\MockServer\Extension\Middleware;

final readonly class WebHookMiddleware implements Middleware
{
    public function __construct(
        private WebHooksFactory $factory,
        private WebHookQueue    $queue,
    ) {}

    public function process(ServerRequest $request, RequestHandler $next): ServerResponse
    {
        $response = $next->handleRequest($request);

        $data = $request->getAttribute('data');
        if ($this->factory->has($data) === false) {
            return $response;
        }
        $this->queue->push(
            $this->factory->create($data),
        );
        return $response;
    }
}
