<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\DataFactory\Condition\ConditionFactory;
use Lav45\MockServer\DataFactory\Condition\ConditionHandler;

final readonly class ConditionMiddleware implements Middleware
{
    public function __construct(
        private ConditionFactory $factory,
        private ConditionHandler $handler,
    ) {}

    public function process(Request $request, MiddlewareHandler $next): Response
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
