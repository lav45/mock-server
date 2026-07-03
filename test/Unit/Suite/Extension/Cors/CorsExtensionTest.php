<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Extension\Cors;

use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Extension\Container;
use Lav45\MockServer\Extension\Cors\CorsExtension;
use Lav45\MockServer\Extension\Cors\CorsMiddleware;
use Lav45\MockServer\Extension\ExtensionType;
use Lav45\MockServer\Test\Unit\Components\CallableHandler;
use Lav45\MockServer\Test\Unit\Components\FakeServerRequest;
use PHPUnit\Framework\TestCase;

final class CorsExtensionTest extends TestCase
{
    private function next(ServerResponse $response): RequestHandler
    {
        return new CallableHandler(static fn(ServerRequest $request): ServerResponse => $response);
    }

    private function create(array $options): CorsMiddleware
    {
        $middleware = new CorsExtension()->create(new Container(), $options);
        $this->assertInstanceOf(CorsMiddleware::class, $middleware);
        return $middleware;
    }

    public function testTypeIsSystem(): void
    {
        $this->assertSame(ExtensionType::System, new CorsExtension()->type());
    }

    public function testDefaultOptionsAllowAnyOrigin(): void
    {
        $middleware = $this->create([]);
        $request = new FakeServerRequest('GET', 'https://localhost/', ['origin' => 'https://any.com']);

        $response = $middleware->process($request, $this->next(new ServerResponse(200)));

        $this->assertSame('*', $response->getHeader('access-control-allow-origin'));
    }

    public function testParsesSimpleRequestOptions(): void
    {
        $middleware = $this->create([
            'allowOrigin' => ' https://a.com , https://b.com ',
            'exposeHeaders' => 'X-Total',
            'allowCredentials' => 1,
        ]);

        $allowed = new FakeServerRequest('GET', 'https://localhost/', ['origin' => 'https://b.com']);
        $response = $middleware->process($allowed, $this->next(new ServerResponse(200)));

        $this->assertSame('https://b.com', $response->getHeader('access-control-allow-origin'));
        $this->assertSame('true', $response->getHeader('access-control-allow-credentials'));
        $this->assertSame('X-Total', $response->getHeader('access-control-expose-headers'));

        $disallowed = new FakeServerRequest('GET', 'https://localhost/', ['origin' => 'https://c.com']);
        $response = $middleware->process($disallowed, $this->next(new ServerResponse(200)));

        $this->assertNull($response->getHeader('access-control-allow-origin'));
    }

    public function testDefaultsCredentialsToFalse(): void
    {
        $middleware = $this->create(['allowOrigin' => 'https://a.com']);
        $request = new FakeServerRequest('GET', 'https://localhost/', ['origin' => 'https://a.com']);

        $response = $middleware->process($request, $this->next(new ServerResponse(200)));

        $this->assertSame('https://a.com', $response->getHeader('access-control-allow-origin'));
        $this->assertNull($response->getHeader('access-control-allow-credentials'));
    }

    public function testParsesPreflightOptions(): void
    {
        $middleware = $this->create([
            'allowOrigin' => 'https://a.com',
            'allowMethods' => 'GET',
            'allowHeaders' => 'X-A',
            'maxAge' => '600',
        ]);

        $request = new FakeServerRequest('OPTIONS', 'https://localhost/', ['origin' => 'https://a.com']);

        $response = $middleware->process($request, $this->next(new ServerResponse(200)));

        $this->assertSame(204, $response->getStatus());
        $this->assertSame('GET', $response->getHeader('access-control-allow-methods'));
        $this->assertSame('X-A', $response->getHeader('access-control-allow-headers'));
        $this->assertSame('600', $response->getHeader('access-control-max-age'));
    }

    public function testArrayOptionsAreJoined(): void
    {
        $middleware = $this->create([
            'allowOrigin' => ['https://a.com'],
            'allowMethods' => ['GET', 'POST'],
            'allowHeaders' => ['X-A', 'X-B'],
            'exposeHeaders' => ['X-Total'],
        ]);

        $request = new FakeServerRequest('OPTIONS', 'https://localhost/', ['origin' => 'https://a.com']);

        $response = $middleware->process($request, $this->next(new ServerResponse(200)));

        $this->assertSame('GET, POST', $response->getHeader('access-control-allow-methods'));
        $this->assertSame('X-A, X-B', $response->getHeader('access-control-allow-headers'));
    }

    public function testDefaultExposeHeadersReflectsResponseHeaders(): void
    {
        $middleware = $this->create(['allowOrigin' => 'https://a.com']);
        $request = new FakeServerRequest('GET', 'https://localhost/', ['origin' => 'https://a.com']);
        $inner = new ServerResponse(200, ['x-request-id' => 'abc']);

        $response = $middleware->process($request, $this->next($inner));

        $this->assertSame('x-request-id', $response->getHeader('access-control-expose-headers'));
    }

    public function testNullExposeHeadersSkipsHeader(): void
    {
        $middleware = $this->create([
            'allowOrigin' => 'https://a.com',
            'exposeHeaders' => null,
        ]);
        $request = new FakeServerRequest('GET', 'https://localhost/', ['origin' => 'https://a.com']);
        $inner = new ServerResponse(200, ['x-request-id' => 'abc']);

        $response = $middleware->process($request, $this->next($inner));

        $this->assertNull($response->getHeader('access-control-expose-headers'));
    }
}
