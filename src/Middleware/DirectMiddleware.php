<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\DataFactory\DirectFactory;
use Lav45\MockServer\Parser\InlineParser;
use Lav45\MockServer\Responder\DirectHandler;

final readonly class DirectMiddleware implements Middleware
{
    public function __construct(
        private DirectFactory $factory,
        private DirectHandler $handler,
    ) {}

    public function process(Request $request, MiddlewareHandler $next): Response
    {
        $data = $request->getAttribute('data');
        if ($this->factory->has($data) === false) {
            return $next->handle($request);
        }

        $dataInjector = $this->handler->request(
            $this->factory->create($request, $data),
        );

        $data = $dataInjector->replace($data);

        /** @var InlineParser $parser */
        $parser = $request->getAttribute('parser');
        $data = $parser->replace($data);

        $request->setAttribute('data', $data);

        return $next->handle($request);
    }
}
