<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Functional\Suite\Mock\Response;

use Amp\Http\Client\Form;
use Lav45\MockServer\Infrastructure\HttpClient\Factory as HttpClientFactory;
use Lav45\MockServer\Infrastructure\HttpClient\HttpClientInterface;
use PHPUnit\Framework\TestCase;

class ProxyTest extends TestCase
{
    private HttpClientInterface $HttpClient;

    protected function setUp(): void
    {
        $this->HttpClient = HttpClientFactory::create();
    }

    public function testPost(): void
    {
        $data = ['text' => 'OK'];

        $response = $this->HttpClient->request(
            uri: 'http://127.0.0.1/response/proxy/storage',
            method: 'POST',
            body: \json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            headers: ['content-type' => 'application/json'],
        );
        $this->assertEquals(200, $response->getStatus());

        $content = $this->getStorageData();

        $this->assertEquals('POST', $content[0]['method']);
        $this->assertEquals([], $content[0]['get']);
        $this->assertEquals($data, $content[0]['post']);

        $this->assertArrayHasKey('content-type', $content[0]['headers']);
        $this->assertEquals('application/json', $content[0]['headers']['content-type'][0]);

        $this->assertArrayHasKey('authorization', $content[0]['headers']);
        $this->assertEquals('Bearer eyJhbGciOiJSUzI1NiJ9', $content[0]['headers']['authorization'][0]);
    }

    public function testGet(): void
    {
        $response = $this->HttpClient->request(
            uri: 'http://127.0.0.1/response/proxy/storage?id=100',
            headers: ['content-type' => 'application/json'],
        );
        $this->assertEquals(200, $response->getStatus());

        $content = $this->getStorageData();

        $this->assertEquals('GET', $content[0]['method']);
        $this->assertEquals(['id' => 100], $content[0]['get']);
        $this->assertEquals([], $content[0]['post']);

        $this->assertArrayHasKey('content-type', $content[0]['headers']);
        $this->assertEquals('application/json', $content[0]['headers']['content-type'][0]);

        $this->assertArrayHasKey('authorization', $content[0]['headers']);
        $this->assertEquals('Bearer eyJhbGciOiJSUzI1NiJ9', $content[0]['headers']['authorization'][0]);
    }

    private function getStorageData(): array
    {
        $response = $this->HttpClient->request('http://127.0.0.1:8000/__storage');
        $content = $response->getBody()->read();
        return \json_decode($content, true);
    }

    public function testArrayContent(): void
    {
        $response = $this->HttpClient->request(
            uri: 'http://127.0.0.1/response/proxy/array-content',
            method: 'POST',
            headers: ['content-type' => 'application/json'],
        );
        $this->assertEquals(200, $response->getStatus());

        $headers = $response->getHeaders();
        $this->assertArrayHasKey('authorization', $headers);
        $this->assertEquals('Bearer eyJhbGciOiJSUzI1NiJ9', $headers['authorization'][0]);

        $content = $response->getBody()->read();
        $content = \json_decode($content, true);

        $this->assertEquals('POST', $content['method']);
        $this->assertEquals(['n' => '1'], $content['get']);

        $this->assertCount(6, $content['post']);

        $expected = ['id' => 3, 'name' => 'name 3'];
        $this->assertEquals($expected, $content['post'][2]);

        $this->assertArrayHasKey('content-type', $content['headers']);
        $this->assertEquals('application/json', $content['headers']['content-type'][0]);
    }

    public function testStringContent(): void
    {
        $response = $this->HttpClient->request(
            uri: 'http://127.0.0.1/response/proxy/string-content',
            method: 'POST',
            body: '{"name": "Company"}',
            headers: ['content-type' => 'application/json'],
        );
        $this->assertEquals(200, $response->getStatus());

        $headers = $response->getHeaders();
        $this->assertArrayHasKey('authorization', $headers);
        $this->assertEquals('Bearer eyJhbGciOiJSUzI1NiJ9', $headers['authorization'][0]);

        $content = $response->getBody()->read();
        $content = \json_decode($content, true);

        $this->assertEquals('POST', $content['method']);
        $this->assertEquals([], $content['get']);

        $this->assertEquals(['id' => 100], $content['post']);

        $this->assertArrayHasKey('content-type', $content['headers']);
        $this->assertEquals('application/json', $content['headers']['content-type'][0]);
    }

    public function testFakerContent(): void
    {
        $response = $this->HttpClient->request(
            uri: 'http://127.0.0.1/response/proxy/faker-content',
            method: 'POST',
            body: '{"name": "Company"}',
            headers: ['content-type' => 'application/json'],
        );
        $this->assertEquals(200, $response->getStatus());

        $headers = $response->getHeaders();
        $this->assertArrayHasKey('authorization', $headers);
        $this->assertEquals('Bearer eyJhbGciOiJSUzI1NiJ9', $headers['authorization'][0]);

        $content = $response->getBody()->read();
        $content = \json_decode($content, true);

        $this->assertEquals('POST', $content['method']);
        $this->assertEquals([], $content['get']);

        $this->assertTrue(isset($content['post']['company']['id']));
        $uuidPattern = '~^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$~';
        $this->assertMatchesRegularExpression($uuidPattern, $content['post']['company']['id']);

        $this->assertArrayHasKey('content-type', $content['headers']);
        $this->assertEquals('application/json', $content['headers']['content-type'][0]);
    }

    public function testFormData(): void
    {
        $data = [
            'id' => 100,
            'item' => [1, 2, 3],
        ];

        $form = new Form();
        foreach ($data as $name => $value) {
            if (\is_array($value)) {
                foreach ($value as $val) {
                    $form->addField($name, (string)$val);
                }
            } else {
                $form->addField($name, (string)$value);
            }
        }

        $response = $this->HttpClient->request(
            uri: 'http://127.0.0.1/response/proxy/storage',
            method: 'POST',
            body: $form->getContent()->read(),
            headers: ['content-type' => $form->getContentType()],
        );
        $this->assertEquals(200, $response->getStatus());

        $content = $this->getStorageData();

        $this->assertEquals('POST', $content[0]['method']);
        $this->assertEquals([], $content[0]['get']);

        $expected = [
            'id' => '100',
            'item' => ['1', '2', '3'],
        ];
        $this->assertEquals($expected, $content[0]['post']);

        $this->assertArrayHasKey('content-type', $content[0]['headers']);
        $this->assertEquals('application/x-www-form-urlencoded', $content[0]['headers']['content-type'][0]);

        $this->assertArrayHasKey('authorization', $content[0]['headers']);
        $this->assertEquals('Bearer eyJhbGciOiJSUzI1NiJ9', $content[0]['headers']['authorization'][0]);
    }
}
