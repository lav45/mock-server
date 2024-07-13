<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Infrastructure\Factory;

use Lav45\MockServer\Application\Data\Mock\v1\Request;
use Lav45\MockServer\Application\Data\Mock\v1\Response;
use Lav45\MockServer\Infrastructure\Factory\Mock as MockFactory;
use PHPUnit\Framework\TestCase;

final class MockTest extends TestCase
{
    public function testDefault(): void
    {
        $object = MockFactory::create([]);

        $this->assertInstanceOf(Request::class, $object->request);
        $this->assertEquals(['GET'], $object->request->method);
        $this->assertEquals('/', $object->request->url);

        $this->assertInstanceOf(Response::class, $object->response);
        $this->assertInstanceOf(Response\Content::class, $object->response->content);
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

        $this->assertInstanceOf(Request::class, $object->request);
        $this->assertInstanceOf(Response::class, $object->response);
        $this->assertEquals([], $object->webhooks);
        $this->assertEquals([], $object->env);

        $this->assertEquals($data['request']['method'], $object->request->method);
        $this->assertEquals($data['request']['url'], $object->request->url);

        $this->assertEquals(0.0, $object->response->delay);

        $this->assertInstanceOf(Response\Content::class, $object->response->content);
        $this->assertEquals(200, $object->response->content->status);
        $this->assertEquals('', $object->response->content->text);
    }
}
