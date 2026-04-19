<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\DataFactory\ParserFactory;

final readonly class ParserMiddleware
{
    public function __construct(
        private ParserFactory $factory,
    ) {}

    public function __invoke(Request $request, \Closure $next): Response
    {
        $data = $request->getAttribute('data');
        $parser = $this->factory->create($request, $data['env'] ?? []);
        $request->setAttribute('parser', $parser);
        return $next($request);
    }
}
