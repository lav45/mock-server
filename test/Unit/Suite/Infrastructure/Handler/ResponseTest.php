<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Infrastructure\Handler;

use Lav45\MockServer\Domain\Entity\Response as ResponseEntity;
use Lav45\MockServer\Infrastructure\Factory\HttpClient as HttpClientFactory;
use Lav45\MockServer\Infrastructure\Handler\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function testInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $data = new class () implements ResponseEntity {};

        (new Response(HttpClientFactory::create()))->create($data);
    }
}
