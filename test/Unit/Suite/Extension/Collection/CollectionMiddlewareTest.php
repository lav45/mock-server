<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Extension\Collection;

use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Extension\Collection\CollectionFactory;
use Lav45\MockServer\Extension\Collection\CollectionMiddleware;
use Lav45\MockServer\Extension\Collection\CollectionResponder;
use Lav45\MockServer\Parser\InlineParser;
use Lav45\MockServer\Parser\ParamParser;
use Lav45\MockServer\Parser\VariableParser;
use Lav45\MockServer\Test\Unit\Components\CallableHandler;
use Lav45\MockServer\Test\Unit\Components\FakeServerRequest;
use PHPUnit\Framework\TestCase;

final class CollectionMiddlewareTest extends TestCase
{
    private function createMiddleware(): CollectionMiddleware
    {
        return new CollectionMiddleware(new CollectionFactory(new DataBuilder()), new CollectionResponder());
    }

    private function createRequest(string $url = 'https://localhost/'): ServerRequest
    {
        return new FakeServerRequest('GET', $url);
    }

    private function createParser(array $data = []): VariableParser
    {
        $parser = new ParamParser(new class implements InlineParser {
            public function replace(mixed $data): mixed
            {
                return $data;
            }
        });
        return $data ? $parser->withData($data) : $parser;
    }

    private function nextReturning(int $status): RequestHandler
    {
        return new CallableHandler(static fn(ServerRequest $r): ServerResponse => new ServerResponse($status));
    }

    private function decodeBody(ServerResponse $response): mixed
    {
        return \json_decode($response->getBody(), true, flags: JSON_THROW_ON_ERROR);
    }

    // --- Passthrough ---

    public function testPassesThroughToNextWhenResponseTypeDoesNotMatch(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', ['response' => ['type' => 'proxy']]);

        $response = ($this->createMiddleware())->process($request, $this->nextReturning(418));

        $this->assertSame(418, $response->getStatus());
    }

    public function testDoesNotCallNextWhenResponseTypeMatches(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', ['response' => ['type' => 'data', 'items' => []]]);

        $response = ($this->createMiddleware())->process($request, $this->nextReturning(418));

        $this->assertNotSame(418, $response->getStatus());
    }

    // --- Response building ---

    public function testReturnsJsonResponseWhenTypeMatches(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', ['response' => ['type' => 'data', 'items' => [['id' => 1], ['id' => 2]]]]);

        $response = ($this->createMiddleware())->process($request, $this->nextReturning(418));

        $this->assertSame(200, $response->getStatus());
        $this->assertSame('application/json', $response->getHeader('content-type'));
        $this->assertSame([['id' => 1], ['id' => 2]], $this->decodeBody($response));
    }

    public function testUsesResponseKeyFromDataAttribute(): void
    {
        $items = [['id' => 10], ['id' => 20]];

        $request = $this->createRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', [
            'env' => ['ignored' => true],
            'response' => ['type' => 'data', 'items' => $items],
        ]);

        $response = ($this->createMiddleware())->process($request, $this->nextReturning(418));

        $this->assertSame($items, $this->decodeBody($response));
    }

    public function testDefaultsToEmptyItemsWhenResponseKeyMissing(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', ['response' => ['type' => 'data']]);

        $response = ($this->createMiddleware())->process($request, $this->nextReturning(418));

        $this->assertSame(200, $response->getStatus());
        $this->assertSame([], $this->decodeBody($response));
    }

    // --- Pagination ---

    public function testPaginatesItemsFromQueryString(): void
    {
        $items = [['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4], ['id' => 5]];

        $request = $this->createRequest('https://localhost/?page=2&per-page=2');
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', ['response' => ['type' => 'data', 'items' => $items]]);

        $response = ($this->createMiddleware())->process($request, $this->nextReturning(418));

        $this->assertSame([['id' => 3], ['id' => 4]], $this->decodeBody($response));
    }

    // --- Parser ---

    public function testUsesParserFromRequestAttribute(): void
    {
        $items = [['name' => '{env.label}']];
        $parser = $this->createParser(['env' => ['label' => 'hello']]);

        $request = $this->createRequest();
        $request->setAttribute('parser', $parser);
        $request->setAttribute('data', ['response' => ['type' => 'data', 'items' => $items]]);

        $response = ($this->createMiddleware())->process($request, $this->nextReturning(418));

        $this->assertSame([['name' => 'hello']], $this->decodeBody($response));
    }
}
