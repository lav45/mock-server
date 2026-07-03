<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Extension\Cors;

use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Extension\Cors\CorsConfig;
use Lav45\MockServer\Extension\Cors\CorsMiddleware;
use Lav45\MockServer\Test\Unit\Components\CallableHandler;
use Lav45\MockServer\Test\Unit\Components\FakeServerRequest;
use PHPUnit\Framework\TestCase;

final class CorsMiddlewareTest extends TestCase
{
    private function next(ServerResponse $response): RequestHandler
    {
        return new CallableHandler(static fn(ServerRequest $request): ServerResponse => $response);
    }

    public function testSimpleRequestAddsWildcardOrigin(): void
    {
        $middleware = new CorsMiddleware(new CorsConfig());
        $request = new FakeServerRequest('GET', 'https://localhost/', ['origin' => 'https://a.com']);

        $response = $middleware->process($request, $this->next(new ServerResponse(200)));

        $this->assertSame(200, $response->getStatus());
        $this->assertSame('*', $response->getHeader('access-control-allow-origin'));
        $this->assertNull($response->getHeader('vary'));
        $this->assertNull($response->getHeader('access-control-allow-methods'));
    }

    public function testSimpleRequestWithCredentialsReflectsOrigin(): void
    {
        $middleware = new CorsMiddleware(new CorsConfig(
            origins: ['https://a.com'],
            exposeHeaders: ['X-Total'],
            allowCredentials: true,
        ));
        $request = new FakeServerRequest('GET', 'https://localhost/', ['origin' => 'https://a.com']);

        $response = $middleware->process($request, $this->next(new ServerResponse(200)));

        $this->assertSame('https://a.com', $response->getHeader('access-control-allow-origin'));
        $this->assertSame('Origin', $response->getHeader('vary'));
        $this->assertSame('true', $response->getHeader('access-control-allow-credentials'));
        $this->assertSame('X-Total', $response->getHeader('access-control-expose-headers'));
    }

    public function testExposeHeadersWildcardReflectsResponseHeaders(): void
    {
        $middleware = new CorsMiddleware(new CorsConfig());
        $request = new FakeServerRequest('GET', 'https://localhost/', ['origin' => 'https://a.com']);
        $inner = new ServerResponse(200, ['x-request-id' => 'abc', 'x-total' => '5']);

        $response = $middleware->process($request, $this->next($inner));

        $this->assertSame('x-request-id, x-total', $response->getHeader('access-control-expose-headers'));
    }

    public function testExposeHeadersWildcardWithoutResponseHeadersSkipsHeader(): void
    {
        $middleware = new CorsMiddleware(new CorsConfig());
        $request = new FakeServerRequest('GET', 'https://localhost/', ['origin' => 'https://a.com']);

        $response = $middleware->process($request, $this->next(new ServerResponse(200)));

        $this->assertNull($response->getHeader('access-control-expose-headers'));
    }

    public function testExposeHeadersNullSkipsHeader(): void
    {
        $middleware = new CorsMiddleware(new CorsConfig(exposeHeaders: null));
        $request = new FakeServerRequest('GET', 'https://localhost/', ['origin' => 'https://a.com']);
        $inner = new ServerResponse(200, ['x-request-id' => 'abc']);

        $response = $middleware->process($request, $this->next($inner));

        $this->assertNull($response->getHeader('access-control-expose-headers'));
    }

    public function testSimpleRequestWithDisallowedOriginSkipsHeaders(): void
    {
        $middleware = new CorsMiddleware(new CorsConfig(origins: ['https://a.com']));
        $request = new FakeServerRequest('GET', 'https://localhost/', ['origin' => 'https://evil.com']);

        $response = $middleware->process($request, $this->next(new ServerResponse(200)));

        $this->assertNull($response->getHeader('access-control-allow-origin'));
    }

    public function testSimpleRequestWithoutOriginHeaderSkipsHeaders(): void
    {
        $middleware = new CorsMiddleware(new CorsConfig(origins: ['https://a.com']));
        $request = new FakeServerRequest('GET', 'https://localhost/');

        $response = $middleware->process($request, $this->next(new ServerResponse(200)));

        $this->assertNull($response->getHeader('access-control-allow-origin'));
    }

    public function testPreflightReflectsRequestedMethodAndHeaders(): void
    {
        $middleware = new CorsMiddleware(new CorsConfig());
        $request = new FakeServerRequest('OPTIONS', 'https://localhost/', [
            'origin' => 'https://a.com',
            'access-control-request-method' => 'PATCH',
            'access-control-request-headers' => 'X-Custom',
        ]);

        $response = $middleware->process($request, $this->next(new ServerResponse(200)));

        $this->assertSame(204, $response->getStatus());
        $this->assertSame('*', $response->getHeader('access-control-allow-origin'));
        $this->assertSame('PATCH', $response->getHeader('access-control-allow-methods'));
        $this->assertSame('X-Custom', $response->getHeader('access-control-allow-headers'));
        $this->assertSame('86400', $response->getHeader('access-control-max-age'));
    }

    public function testPreflightWithConfiguredMethodsHeadersAndMaxAge(): void
    {
        $middleware = new CorsMiddleware(new CorsConfig(
            allowMethods: ['GET'],
            allowHeaders: ['X-A'],
            maxAge: 600,
        ));
        $request = new FakeServerRequest('OPTIONS', 'https://localhost/', ['origin' => 'https://a.com']);

        $response = $middleware->process($request, $this->next(new ServerResponse(200)));

        $this->assertSame(204, $response->getStatus());
        $this->assertSame('GET', $response->getHeader('access-control-allow-methods'));
        $this->assertSame('X-A', $response->getHeader('access-control-allow-headers'));
        $this->assertSame('600', $response->getHeader('access-control-max-age'));
    }

    public function testPreflightWildcardMethodsAndHeadersFallBackToStar(): void
    {
        $middleware = new CorsMiddleware(new CorsConfig());
        $request = new FakeServerRequest('OPTIONS', 'https://localhost/', ['origin' => 'https://a.com']);

        $response = $middleware->process($request, $this->next(new ServerResponse(200)));

        $this->assertSame('*', $response->getHeader('access-control-allow-methods'));
        $this->assertSame('*', $response->getHeader('access-control-allow-headers'));
    }

    public function testPreflightWithDisallowedOriginSkipsCorsHeaders(): void
    {
        $middleware = new CorsMiddleware(new CorsConfig(origins: ['https://a.com']));
        $request = new FakeServerRequest('OPTIONS', 'https://localhost/', [
            'origin' => 'https://evil.com',
            'access-control-request-method' => 'PATCH',
        ]);

        $response = $middleware->process($request, $this->next(new ServerResponse(200)));

        $this->assertSame(204, $response->getStatus());
        $this->assertNull($response->getHeader('access-control-allow-origin'));
        $this->assertNull($response->getHeader('access-control-allow-methods'));
    }
}
