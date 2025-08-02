<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Infrastructure\Handler;

use Lav45\MockServer\Domain\Model\Response as ResponseEntity;
use Lav45\MockServer\Infrastructure\Handler\ResponseFabric;
use Lav45\MockServer\Infrastructure\HttpClient\Factory as HttpClientFactory;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function testInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $data = new class implements ResponseEntity {};

        (new ResponseFabric(HttpClientFactory::create()))->create($data);
    }
}
