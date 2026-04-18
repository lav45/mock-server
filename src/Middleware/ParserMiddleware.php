<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\DataFactory\RequestParserContext;

final readonly class ParserMiddleware
{
    public function __construct(
        private RequestParserContext $parserContext,
    ) {}

    public function __invoke(Request $request, \Closure $next): Response
    {
        $data = $request->getAttribute('data');
        $parser = $this->parserContext->create($request, $data);
        $request->setAttribute('parser', $parser);
        return $next($request);
    }
}
