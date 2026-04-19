<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\DataFactory\DirectFactory;
use Lav45\MockServer\Helper\ArrayHelper;
use Lav45\MockServer\Responder\DirectHandler;

final readonly class DirectMiddleware
{
    public function __construct(
        private DirectFactory $factory,
        private DirectHandler $handler,
    ) {}

    public function __invoke(Request $request, \Closure $next): Response
    {
        $data = $request->getAttribute('data');
        if (isset($data[DirectFactory::TYPE]) === false) {
            return $next($request);
        }

        $directData = $this->handler->request(
            $this->factory->create(
                request: $request,
                parser: $request->getAttribute('parser'),
                data: $data[DirectFactory::TYPE],
            ),
        );

        $directData = ArrayHelper::map($directData, static function (string $value): string {
            return \str_replace(['\\{', '\\}'], ['{', '}'], $value);
        });

        if (isset($directData['response'])) {
            $data['response'] = $directData['response'];
        }
        if (isset($directData['webhooks'])) {
            $data['webhooks'] = $directData['webhooks'];
        }

        $request->setAttribute('data', $data);

        return $next($request);
    }
}
