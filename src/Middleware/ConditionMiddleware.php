<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\DataFactory\Condition\ConditionHandler;
use Lav45\MockServer\DataFactory\ConditionFactory;

final readonly class ConditionMiddleware
{
    public function __construct(
        private ConditionFactory $factory,
        private ConditionHandler $handler,
    ) {}

    public function __invoke(Request $request, \Closure $next): Response
    {
        $data = $request->getAttribute('data');
        if (isset($data[ConditionFactory::TYPE]) === false) {
            return $next($request);
        }

        $conditionData = $this->handler->request(
            conditions: $this->factory->create($data[ConditionFactory::TYPE]),
            parser: $request->getAttribute('parser'),
        );

        if (isset($conditionData['response'])) {
            $data['response'] = $conditionData['response'];
        }
        if (isset($conditionData['webhooks'])) {
            $data['webhooks'] = $conditionData['webhooks'];
        }

        $request->setAttribute('data', $data);

        return $next($request);
    }
}
