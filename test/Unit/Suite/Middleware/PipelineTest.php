<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Middleware;

use Lav45\MockServer\Middleware\Pipeline;
use PHPUnit\Framework\TestCase;

final class PipelineTest extends TestCase
{
    public function testSingleMiddlewareReceivesNullAsNext(): void
    {
        $receivedNext = 'not-set';
        $middleware = static function ($request, $next) use (&$receivedNext): string {
            $receivedNext = $next;
            return 'done';
        };

        $pipeline = Pipeline::create($middleware);
        $pipeline('request');

        $this->assertNull($receivedNext);
    }

    public function testExecutesMiddlewaresInOrder(): void
    {
        $log = [];

        $mw1 = static function ($request, $next) use (&$log): string {
            $log[] = 'mw1';
            return $next($request);
        };
        $mw2 = static function ($request, $next) use (&$log): string {
            $log[] = 'mw2';
            return $next($request);
        };
        $mw3 = static function ($request, $next) use (&$log): string {
            $log[] = 'mw3';
            return 'end';
        };

        $pipeline = Pipeline::create($mw1, $mw2, $mw3);
        $pipeline('request');

        $this->assertSame(['mw1', 'mw2', 'mw3'], $log);
    }

    public function testPassesRequestThroughChain(): void
    {
        $receivedRequests = [];

        $mw1 = static function ($request, $next) use (&$receivedRequests) {
            $receivedRequests[] = $request;
            return $next($request);
        };
        $mw2 = static function ($request, $next) use (&$receivedRequests) {
            $receivedRequests[] = $request;
            return 'end';
        };

        $pipeline = Pipeline::create($mw1, $mw2);
        $pipeline('my-request');

        $this->assertSame(['my-request', 'my-request'], $receivedRequests);
    }

    public function testReturnsResultFromFirstMiddleware(): void
    {
        $mw1 = static function ($request, $next): string {
            return 'outer:' . $next($request);
        };
        $mw2 = static function ($request, $next): string {
            return 'inner';
        };

        $pipeline = Pipeline::create($mw1, $mw2);
        $result = $pipeline('request');

        $this->assertSame('outer:inner', $result);
    }
}
