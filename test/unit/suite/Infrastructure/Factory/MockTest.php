<?php declare(strict_types=1);

namespace lav45\MockServer\test\unit\suite\Infrastructure\Factory;

use lav45\MockServer\Application\DTO\Mock\v1\Request as RequestDTO;
use lav45\MockServer\Application\DTO\Mock\v1\Response as ResponseDTO;
use lav45\MockServer\Application\DTO\Mock\v1\Response\Content as ResponseContentDTO;
use lav45\MockServer\Infrastructure\Factory\Mock as MockFactory;
use PHPUnit\Framework\TestCase;

final class MockTest extends TestCase
{
    public function testDefault(): void
    {
        $object = MockFactory::create([]);

        $this->assertInstanceOf(RequestDTO::class, $object->request);
        $this->assertEquals(['GET'], $object->request->method);
        $this->assertEquals('/', $object->request->url);

        $this->assertInstanceOf(ResponseDTO::class, $object->response);
        $this->assertInstanceOf(ResponseContentDTO::class, $object->response->content);
        $this->assertEquals([], $object->webhooks);
        $this->assertEquals([], $object->env);
    }

    public function testRequest(): void
    {
        $data = [
            'request' => [
                'method' => 'POST',
                'url' => '/webhook/{id}',
            ],
        ];

        $object = MockFactory::create($data);

        $this->assertInstanceOf(RequestDTO::class, $object->request);
        $this->assertInstanceOf(ResponseDTO::class, $object->response);
        $this->assertEquals([], $object->webhooks);
        $this->assertEquals([], $object->env);

        $this->assertEquals($data['request']['method'], $object->request->method);
        $this->assertEquals($data['request']['url'], $object->request->url);

        $this->assertEquals(0.0, $object->response->delay);

        $this->assertInstanceOf(ResponseContentDTO::class, $object->response->content);
        $this->assertEquals(200, $object->response->content->status);
        $this->assertEquals('', $object->response->content->text);
    }
}
