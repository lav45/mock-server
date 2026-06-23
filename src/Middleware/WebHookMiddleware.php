<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Lav45\MockServer\DataFactory\WebHooksFactory;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Engine\WebHookQueue;

final readonly class WebHookMiddleware implements Middleware
{
    public function __construct(
        private WebHooksFactory $factory,
        private WebHookQueue    $queue,
    ) {}

    public function process(ServerRequest $request, MiddlewareHandler $next): ServerResponse
    {
        $response = $next->handle($request);

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
