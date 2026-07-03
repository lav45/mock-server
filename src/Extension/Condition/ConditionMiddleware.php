<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Condition;

use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Extension\Middleware;

final readonly class ConditionMiddleware implements Middleware
{
    public function __construct(
        private ConditionFactory $factory,
        private ConditionHandler $handler,
    ) {}

    public function process(ServerRequest $request, RequestHandler $next): ServerResponse
    {
        $data = $request->getAttribute('data');
        if ($this->factory->has($data) === false) {
            return $next->handleRequest($request);
        }

        $data = $this->handler->request(
            conditions: $this->factory->create($data)->items,
        )->replace($data);

        $request->setAttribute('data', $data);

        return $next->handleRequest($request);
    }
}
