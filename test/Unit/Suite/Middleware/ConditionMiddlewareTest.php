<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Middleware;

use Lav45\MockServer\DataFactory\Condition\ConditionFactory;
use Lav45\MockServer\DataFactory\Condition\ConditionHandler;
use Lav45\MockServer\DataFactory\Condition\SpecificationFactory;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Middleware\ConditionMiddleware;
use Lav45\MockServer\Parser\InlineParser;
use Lav45\MockServer\Parser\ParamParser;
use Lav45\MockServer\Test\Unit\Components\CallableHandler;
use Lav45\MockServer\Test\Unit\Components\FakeServerRequest;
use PHPUnit\Framework\TestCase;

final class ConditionMiddlewareTest extends TestCase
{
    /**
     * Resolves the request data (as PrepareMiddleware does at request time),
     * then runs the middleware against the already-resolved data.
     */
    private function invoke(ServerRequest $request, \Closure $next): ServerResponse
    {
        $middleware = new ConditionMiddleware(
            new ConditionFactory(new SpecificationFactory()),
            new ConditionHandler(),
        );
        /** @var InlineParser $parser */
        $parser = $request->getAttribute('parser');
        $request->setAttribute('data', $parser->replace($request->getAttribute('data')));
        return $middleware->process($request, new CallableHandler($next));
    }

    private function createRequest(
        string $method = 'GET',
        string $url = 'https://localhost/',
        array  $body = [],
        array  $params = [],
        array  $headers = [],
        array  $env = [],
    ): ServerRequest {
        $request = new FakeServerRequest($method, $url);
        $request->setAttribute('params', $params);

        \parse_str(\parse_url($url, PHP_URL_QUERY) ?? '', $query);

        $inner = new class implements InlineParser {
            public function replace(mixed $data): mixed
            {
                return $data;
            }
        };
        $parser = new ParamParser($inner)->withData([
            'request' => [
                'method' => $method,
                'path' => \parse_url($url, PHP_URL_PATH) ?? '/',
                'query' => $query,
                'headers' => $headers,
                'params' => $params,
                'body' => $body,
            ],
            'env' => $env,
        ]);
        $request->setAttribute('parser', $parser);

        return $request;
    }

    private function next(): \Closure
    {
        return static fn(ServerRequest $r): ServerResponse => new ServerResponse(200);
    }

    private function nextCapturing(array &$captured): \Closure
    {
        return static function (ServerRequest $r) use (&$captured): ServerResponse {
            $captured = $r->getAttribute('data');
            return new ServerResponse(200);
        };
    }

    // --- Passthrough ---

    public function testPassesThroughWhenNoConditionsKey(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', ['response' => ['status' => 200]]);

        $response = $this->invoke($request, $this->next());

        $this->assertSame(200, $response->getStatus());
    }

    public function testDataUnchangedWhenNoConditionsMatch(): void
    {
        $request = $this->createRequest('POST', 'https://localhost/', ['amount' => 50]);
        $request->setAttribute('data', [
            'conditions' => [
                [
                    'match' => ['>', '{{request.body.amount}}', 100],
                    'response' => ['status' => 402],
                ],
            ],
            'response' => ['status' => 200],
        ]);

        $captured = [];
        $this->invoke($request, $this->nextCapturing($captured));

        $this->assertSame(['status' => 200], $captured['response']);
    }

    // --- Condition matching ---

    public function testOverridesResponseOnMatch(): void
    {
        $request = $this->createRequest('POST', 'https://localhost/', ['amount' => 1500]);
        $request->setAttribute('data', [
            'conditions' => [
                [
                    'match' => ['>', '{{request.body.amount}}', 1000],
                    'response' => ['status' => 402, 'body' => ['error' => 'limit exceeded']],
                ],
            ],
            'response' => ['status' => 200],
        ]);

        $captured = [];
        $this->invoke($request, $this->nextCapturing($captured));

        $this->assertSame(402, $captured['response']['status']);
        $this->assertSame(['error' => 'limit exceeded'], $captured['response']['body']);
    }

    public function testFirstMatchWins(): void
    {
        $request = $this->createRequest('POST', 'https://localhost/', ['amount' => 1500]);
        $request->setAttribute('data', [
            'conditions' => [
                [
                    'match' => ['>', '{{request.body.amount}}', 1000],
                    'response' => ['status' => 402],
                ],
                [
                    'match' => ['>', '{{request.body.amount}}', 500],
                    'response' => ['status' => 403],
                ],
            ],
            'response' => ['status' => 200],
        ]);

        $captured = [];
        $this->invoke($request, $this->nextCapturing($captured));

        $this->assertSame(402, $captured['response']['status']);
    }

    // --- Webhooks ---

    public function testOverridesWebhooksWhenConditionHasWebhooksKey(): void
    {
        $request = $this->createRequest('POST', 'https://localhost/', ['type' => 'vip']);
        $request->setAttribute('data', [
            'conditions' => [
                [
                    'match' => ['=', '{{request.body.type}}', 'vip'],
                    'response' => ['status' => 200],
                    'webhooks' => [['url' => 'https://vip.example.com/hook']],
                ],
            ],
            'response' => ['status' => 200],
            'webhooks' => [['url' => 'https://default.example.com/hook']],
        ]);

        $captured = [];
        $this->invoke($request, $this->nextCapturing($captured));

        $this->assertSame([['url' => 'https://vip.example.com/hook']], $captured['webhooks']);
    }

    public function testSuppressesWebhooksWithEmptyArray(): void
    {
        $request = $this->createRequest('POST', 'https://localhost/', ['dry_run' => true]);
        $request->setAttribute('data', [
            'conditions' => [
                [
                    'match' => ['=', '{{request.body.dry_run}}', true],
                    'response' => ['status' => 200],
                    'webhooks' => [],
                ],
            ],
            'response' => ['status' => 200],
            'webhooks' => [['url' => 'https://example.com/hook']],
        ]);

        $captured = [];
        $this->invoke($request, $this->nextCapturing($captured));

        $this->assertSame([], $captured['webhooks']);
    }

    public function testKeepsRootWebhooksWhenConditionOmitsWebhooksKey(): void
    {
        $request = $this->createRequest('POST', 'https://localhost/', ['flag' => 'x']);
        $request->setAttribute('data', [
            'conditions' => [
                [
                    'match' => ['=', '{{request.body.flag}}', 'x'],
                    'response' => ['status' => 201],
                ],
            ],
            'response' => ['status' => 200],
            'webhooks' => [['url' => 'https://example.com/hook']],
        ]);

        $captured = [];
        $this->invoke($request, $this->nextCapturing($captured));

        $this->assertSame([['url' => 'https://example.com/hook']], $captured['webhooks']);
    }

    // --- Data sources ---

    public function testMatchesByQueryParameter(): void
    {
        $request = $this->createRequest('GET', 'https://localhost/?dry_run=true');
        $request->setAttribute('data', [
            'conditions' => [
                [
                    'match' => ['=', '{{request.query.dry_run}}', 'true'],
                    'response' => ['status' => 204],
                ],
            ],
            'response' => ['status' => 200],
        ]);

        $captured = [];
        $this->invoke($request, $this->nextCapturing($captured));

        $this->assertSame(204, $captured['response']['status']);
    }

    public function testMatchesByHeader(): void
    {
        $request = $this->createRequest('GET', 'https://localhost/', [], [], ['x-role' => 'admin']);
        $request->setAttribute('data', [
            'conditions' => [
                [
                    'match' => ['=', '{{request.headers.x-role}}', 'admin'],
                    'response' => ['status' => 403],
                ],
            ],
            'response' => ['status' => 200],
        ]);

        $captured = [];
        $this->invoke($request, $this->nextCapturing($captured));

        $this->assertSame(403, $captured['response']['status']);
    }

    public function testMatchesByRouteParam(): void
    {
        $request = $this->createRequest('GET', 'https://localhost/api/test-42', [], ['id' => 'test-42']);
        $request->setAttribute('data', [
            'conditions' => [
                [
                    'match' => ['~', '{{request.params.id}}', '^test-'],
                    'response' => ['status' => 200, 'body' => ['mode' => 'test']],
                ],
            ],
            'response' => ['status' => 200, 'body' => ['mode' => 'prod']],
        ]);

        $captured = [];
        $this->invoke($request, $this->nextCapturing($captured));

        $this->assertSame('test', $captured['response']['body']['mode']);
    }

    public function testMatchesByMethod(): void
    {
        $request = $this->createRequest('DELETE');
        $request->setAttribute('data', [
            'conditions' => [
                [
                    'match' => ['=', '{{request.method}}', 'DELETE'],
                    'response' => ['status' => 405],
                ],
            ],
            'response' => ['status' => 200],
        ]);

        $captured = [];
        $this->invoke($request, $this->nextCapturing($captured));

        $this->assertSame(405, $captured['response']['status']);
    }

    public function testMatchesByPath(): void
    {
        $request = $this->createRequest('GET', 'https://localhost/api/v2/users');
        $request->setAttribute('data', [
            'conditions' => [
                [
                    'match' => ['~', '{{request.path}}', '^/api/v2/'],
                    'response' => ['status' => 301],
                ],
            ],
            'response' => ['status' => 200],
        ]);

        $captured = [];
        $this->invoke($request, $this->nextCapturing($captured));

        $this->assertSame(301, $captured['response']['status']);
    }

    // --- AND / OR ---

    public function testAndMatch(): void
    {
        $request = $this->createRequest('POST', 'https://localhost/', ['amount' => 1500, 'currency' => 'USD']);
        $request->setAttribute('data', [
            'conditions' => [
                [
                    'match' => ['and',
                        ['>', '{{request.body.amount}}', 1000],
                        ['in', '{{request.body.currency}}', ['USD', 'EUR']],
                    ],
                    'response' => ['status' => 402],
                ],
            ],
            'response' => ['status' => 200],
        ]);

        $captured = [];
        $this->invoke($request, $this->nextCapturing($captured));

        $this->assertSame(402, $captured['response']['status']);
    }

    public function testOrMatch(): void
    {
        $request = $this->createRequest('GET', 'https://localhost/', [], [], ['x-dry-run' => '1']);
        $request->setAttribute('data', [
            'conditions' => [
                [
                    'match' => ['or',
                        ['=', '{{request.query.dry_run}}', 'true'],
                        ['exists', '{{request.headers.x-dry-run}}'],
                    ],
                    'response' => ['status' => 204],
                ],
            ],
            'response' => ['status' => 200],
        ]);

        $captured = [];
        $this->invoke($request, $this->nextCapturing($captured));

        $this->assertSame(204, $captured['response']['status']);
    }

    // --- Empty match ---

    public function testEmptyMatchBlockAlwaysMatches(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', [
            'conditions' => [
                [
                    'match' => [],
                    'response' => ['status' => 418],
                ],
            ],
            'response' => ['status' => 200],
        ]);

        $captured = [];
        $this->invoke($request, $this->nextCapturing($captured));

        $this->assertSame(418, $captured['response']['status']);
    }

    // --- Env value in condition ---

    public function testMatchesWithEnvValue(): void
    {
        $request = $this->createRequest('POST', 'https://localhost/', ['amount' => 1500], [], [], ['limit' => 1000]);
        $request->setAttribute('data', [
            'conditions' => [
                [
                    'match' => ['>', '{{request.body.amount}}', '{{env.limit}}'],
                    'response' => ['status' => 402],
                ],
            ],
            'response' => ['status' => 200],
        ]);

        $captured = [];
        $this->invoke($request, $this->nextCapturing($captured));

        $this->assertSame(402, $captured['response']['status']);
    }
}
