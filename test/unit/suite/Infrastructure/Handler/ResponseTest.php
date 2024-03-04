<?php declare(strict_types=1);

namespace lav45\MockServer\test\unit\suite\Infrastructure\Handler;

use lav45\MockServer\Infrastructure\Factory\HttpClient as HttpClientFactory;
use PHPUnit\Framework\TestCase;
use lav45\MockServer\Infrastructure\Handler\Response;
use lav45\MockServer\Domain\Entity\Response as ResponseEntity;

final class ResponseTest extends TestCase
{
    public function testInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $data = new class implements ResponseEntity {};

        (new Response(HttpClientFactory::create()))->create($data);
    }
}