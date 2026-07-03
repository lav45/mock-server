<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Cors;

use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Extension\Middleware;

final readonly class CorsMiddleware implements Middleware
{
    public function __construct(
        private CorsConfig $config,
    ) {}

    public function process(ServerRequest $request, RequestHandler $next): ServerResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return $this->handlePreflight($request);
        }

        $response = $next->handleRequest($request);
        $this->applyCorsHeaders($request, $response);
        return $response;
    }

    private function handlePreflight(ServerRequest $request): ServerResponse
    {
        $response = new ServerResponse();
        $response->setStatus(204);

        $origin = $this->getAllowOrigin($request);
        if ($origin === null) {
            return $response;
        }

        $this->applyOriginHeaders($response, $origin);
        $response->setHeader('access-control-allow-methods', $this->resolveAllowMethods($request));
        $response->setHeader('access-control-allow-headers', $this->resolveAllowHeaders($request));
        if ($this->config->maxAge !== null) {
            $response->setHeader('access-control-max-age', (string)$this->config->maxAge);
        }

        return $response;
    }

    private function getAllowOrigin(ServerRequest $request): string|null
    {
        return $this->resolveOrigin($request->getHeader('origin'));
    }

    private function applyCorsHeaders(ServerRequest $request, ServerResponse $response): void
    {
        $origin = $this->getAllowOrigin($request);
        if ($origin === null) {
            return;
        }

        $exposeHeaders = $this->resolveExposeHeaders($response->getHeaders());
        if ($exposeHeaders !== null) {
            $response->setHeader('access-control-expose-headers', $exposeHeaders);
        }

        $this->applyOriginHeaders($response, $origin);
    }

    private function applyOriginHeaders(ServerResponse $response, string $origin): void
    {
        $response->setHeader('access-control-allow-origin', $origin);
        if ($origin !== '*') {
            $response->setHeader('vary', 'Origin');
        }

        if ($this->config->allowCredentials) {
            $response->setHeader('access-control-allow-credentials', 'true');
        }
    }

    /**
     * @param array<string, string|list<string>> $responseHeaders
     */
    private function resolveExposeHeaders(array $responseHeaders): string|null
    {
        if ($this->config->exposeHeaders === null) {
            return null;
        }
        if ($this->config->exposeHeaders === ['*']) {
            $names = \array_keys($responseHeaders);
            return $names === [] ? null : \implode(', ', $names);
        }
        return \implode(', ', $this->config->exposeHeaders);
    }

    private function resolveOrigin(string|null $requestOrigin): string|null
    {
        if ($this->config->allowCredentials === false && $this->config->allowsAnyOrigin()) {
            return '*';
        }
        if ($requestOrigin === null) {
            return null;
        }
        return $this->config->allowsOrigin($requestOrigin) ? $requestOrigin : null;
    }

    private function resolveAllowMethods(ServerRequest $request): string
    {
        if ($this->config->allowMethods === ['*']) {
            return $request->getHeader('access-control-request-method') ?? '*';
        }
        return \implode(', ', $this->config->allowMethods);
    }

    private function resolveAllowHeaders(ServerRequest $request): string
    {
        if ($this->config->allowHeaders === ['*']) {
            return $request->getHeader('access-control-request-headers') ?? '*';
        }
        return \implode(', ', $this->config->allowHeaders);
    }
}
