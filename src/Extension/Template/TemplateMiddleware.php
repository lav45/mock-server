<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Template;

use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Extension\Middleware;
use Lav45\MockServer\Parser\VariableParser;

final readonly class TemplateMiddleware implements Middleware
{
    public function __construct(
        private TemplateResolver $resolver,
    ) {}

    public function process(ServerRequest $request, RequestHandler $next): ServerResponse
    {
        $data = $request->getAttribute('data');

        /** @var VariableParser $parser */
        $parser = $request->getAttribute('parser');
        $data = $this->resolver->resolve($data, $parser);

        $request->setAttribute('data', $data);

        return $next->handleRequest($request);
    }
}
