<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Lav45\MockServer\DataFactory\Condition\ConditionFactory;
use Lav45\MockServer\DataFactory\Condition\ConditionHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;

final readonly class ConditionMiddleware implements Middleware
{
    public function __construct(
        private ConditionFactory $factory,
        private ConditionHandler $handler,
    ) {}

    public function process(ServerRequest $request, MiddlewareHandler $next): ServerResponse
    {
        $data = $request->getAttribute('data');
        if ($this->factory->has($data) === false) {
            return $next->handle($request);
        }

        $dataInjector = $this->handler->request(
            conditions: $this->factory->create($data)->items,
        );
        $data = $dataInjector->replace($data);

        $request->setAttribute('data', $data);

        return $next->handle($request);
    }
}
