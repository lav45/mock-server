<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Bootstrap\Migrator;

use Lav45\MockServer\Bootstrap\Migrator\Pipeline;
use PHPUnit\Framework\TestCase;

final class PipelineTest extends TestCase
{
    public function testInnermostStageReceivesNullAsNext(): void
    {
        $receivedNext = 'not-set';
        $stage = static function ($next) use (&$receivedNext): callable {
            $receivedNext = $next;
            return static fn(array $data): array => $data;
        };

        Pipeline::create($stage)(['a' => 1]);

        $this->assertNull($receivedNext);
    }

    public function testExecutesStagesInOrder(): void
    {
        $log = [];
        $stage = static function (string $name) use (&$log): callable {
            return static function ($next) use ($name, &$log): callable {
                return static function (array $data) use ($name, $next, &$log): array {
                    $log[] = $name;
                    return $next === null ? $data : $next($data);
                };
            };
        };

        Pipeline::create($stage('a'), $stage('b'), $stage('c'))([]);

        $this->assertSame(['a', 'b', 'c'], $log);
    }

    public function testWrapsOuterStageAroundInner(): void
    {
        $wrap = static function (string $label): callable {
            return static function ($next) use ($label): callable {
                return static function (array $data) use ($label, $next): array {
                    $data[] = "open:{$label}";
                    $data = $next === null ? $data : $next($data);
                    $data[] = "close:{$label}";
                    return $data;
                };
            };
        };

        $result = Pipeline::create($wrap('outer'), $wrap('inner'))([]);

        $this->assertSame(['open:outer', 'open:inner', 'close:inner', 'close:outer'], $result);
    }
}
