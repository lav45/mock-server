<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\DataFactory\WebHooksFactory;
use Lav45\MockServer\Responder\WebHookHandler;

final readonly class WebHookMiddleware
{
    public function __construct(
        private WebHooksFactory $factory,
        private WebHookHandler  $handler,
    ) {}

    public function __invoke(Request $request, \Closure $next): Response
    {
        $response = $next($request);

        $data = $request->getAttribute('data')['webhooks'] ?? [];
        if (empty($data)) {
            return $response;
        }

        $this->handler->send(
            $this->factory->create(
                parser: $request->getAttribute('parser'),
                data: $data,
            ),
        );

        return $response;
    }
}
