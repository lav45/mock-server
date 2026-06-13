<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\DataFactory\WebHooksFactory;
use Lav45\MockServer\Responder\WebHookHandler;

final readonly class WebHookMiddleware implements Middleware
{
    public function __construct(
        private WebHooksFactory $factory,
        private WebHookHandler  $handler,
    ) {}

    public function process(Request $request, MiddlewareHandler $next): Response
    {
        $response = $next->handle($request);

        $data = $request->getAttribute('data');
        if ($this->factory->has($data) === false) {
            return $response;
        }
        $this->handler->send(
            $this->factory->create($data),
        );
        return $response;
    }
}
