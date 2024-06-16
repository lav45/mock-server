<?php declare(strict_types=1);

namespace lav45\MockServer\test\functional\suite\Mock;

use lav45\MockServer\Infrastructure\Factory\HttpClient as HttpClientFactory;
use lav45\MockServer\Infrastructure\Wrapper\HttpClient;
use PHPUnit\Framework\TestCase;

use function Amp\delay;

class WebhookTest extends TestCase
{
    private HttpClient $HttpClient;

    protected function setUp(): void
    {
        $this->HttpClient = HttpClientFactory::create();
    }

    public function testIndex(): void
    {
        $data = [
            'id' => 100,
            's' => 'item',
            'b' => true,
            'n' => null,
            'f' => 0.1,
            'a' => ['item'],
        ];

        $response = $this->HttpClient->request(
            uri: 'http://127.0.0.1/webhook/200?id=500',
            method: 'POST',
            body: \json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            headers: ['content-type' => 'application/json'],
        );
        $this->assertEquals(200, $response->getStatus());

        delay(1);

        $response = $this->HttpClient->request('http://127.0.0.1:8000/__storage');
        $this->assertEquals(200, $response->getStatus());

        $content = $response->getBody()->buffer();
        $content = \json_decode($content, true);

        $this->assertEquals('POST', $content[0]['method']);
        $this->assertEquals([], $content[0]['get']);
        $this->assertEquals([], $content[0]['post']);

        $delay = \round($content[1]['time'] - $content[0]['time'], 2);
        $this->assertTrue($delay >= 0.5, \var_export($delay, true));

        $this->assertEquals('POST', $content[1]['method']);
        $this->assertEquals([], $content[1]['get']);
        $this->assertEquals(['text' => 'Hello world'], $content[1]['post']);

        $this->assertEquals('POST', $content[2]['method']);
        $this->assertSame(['id' => '300'], $content[2]['get']);

        $expected = [
            'ID1' => 'ID: 500',
            'ID2' => '500',
            'ID3' => 'ID: 100',
            'ID4' => 100,
            'get' => ['id' => '500'],
            'post' => $data,
            'urlParams' => ['id' => '200'],
            'urlParamsId' => '200',
        ];
        $this->assertSame($expected, $content[2]['post']);

        $this->assertArrayHasKey('content-type', $content[2]['headers']);
        $this->assertEquals('application/json', $content[2]['headers']['content-type'][0]);

        $this->assertEquals('PUT', $content[3]['method']);
        $this->assertEquals([], $content[3]['get']);
        $this->assertCount(4, $content[3]['post']);

        $this->assertArrayHasKey('id', $content[3]['post'][0]);
        $uuidPattern = '~^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$~';
        $this->assertMatchesRegularExpression($uuidPattern, $content[3]['post'][0]['id']);
        $this->assertArrayHasKey('name', $content[3]['post'][0]);

        $this->assertArrayHasKey('x-api-token', $content[3]['headers']);
        $this->assertEquals('e71ad173-dacf-493c-be55-643074fdf41c', $content[3]['headers']['x-api-token'][0]);

        $this->assertArrayHasKey('content-type', $content[3]['headers']);
        $this->assertEquals('application/json', $content[3]['headers']['content-type'][0]);

        $this->assertEquals('GET', $content[4]['method']);
        $this->assertEquals(['sss' => 'get'], $content[4]['get']);
        $this->assertEquals([], $content[4]['post']);
        $this->assertArrayHasKey('x-api-token', $content[4]['headers']);
        $this->assertEquals('e71ad173-dacf-493c-be55-643074fdf41c', $content[4]['headers']['x-api-token'][0]);

        $this->assertEquals('DELETE', $content[5]['method']);
        $this->assertEquals([], $content[5]['get']);
        $this->assertEquals([], $content[5]['post']);
        $this->assertArrayHasKey('x-api-token', $content[5]['headers']);
        $this->assertEquals('e71ad173-dacf-493c-be55-643074fdf41c', $content[5]['headers']['x-api-token'][0]);

        $this->assertEquals('POST', $content[6]['method']);
        $this->assertArrayHasKey('content-type', $content[6]['headers']);
        $this->assertEquals('application/x-www-form-urlencoded', $content[6]['headers']['content-type'][0]);
        $this->assertSame(['name' => 'John', 'age' => '12'], $content[6]['post']);

        $this->assertEquals('POST', $content[7]['method']);
        $this->assertArrayHasKey('content-type', $content[7]['headers']);
        $this->assertEquals('multipart/form-data; boundary=FB', $content[7]['headers']['content-type'][0]);
        $this->assertSame(['name' => 'John', 'age' => '12'], $content[7]['post']);

        $this->assertEquals('POST', $content[8]['method']);
        $this->assertArrayHasKey('x-api-token', $content[8]['headers']);
        $this->assertEquals('e71ad173-dacf-493c-be55-643074fdf41c', $content[8]['headers']['x-api-token'][0]);
        $this->assertArrayHasKey('content-type', $content[8]['headers']);
        $this->assertEquals('application/json', $content[8]['headers']['content-type'][0]);
        $this->assertMatchesRegularExpression($uuidPattern, $content[8]['post']['uuid']);
        $this->assertMatchesRegularExpression('~^TEST\d{4}$~', $content[8]['post']['id']);
        $this->assertMatchesRegularExpression('~^\d{12}$~', $content[8]['post']['correlationId']);

        $this->assertEquals('POST', $content[9]['method']);
        $this->assertSame(['text' => 'OK'], $content[9]['post']);
    }
}
