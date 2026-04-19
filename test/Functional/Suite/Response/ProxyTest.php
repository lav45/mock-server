<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Functional\Suite\Response;

use Amp\Http\Client\Form;
use Lav45\MockServer\Responder\HttpClient;
use Lav45\MockServer\Responder\HttpClient\Factory as HttpClientFactory;
use League\Uri\Uri;
use PHPUnit\Framework\TestCase;

class ProxyTest extends TestCase
{
    private HttpClient $HttpClient;

    protected function setUp(): void
    {
        $this->HttpClient = new HttpClientFactory()->create();
    }

    public function testPost(): void
    {
        $data = ['text' => 'OK'];

        $response = $this->HttpClient->request(
            uri: MOCK_SERVER_URL . '/response/proxy/storage',
            method: 'POST',
            headers: ['content-type' => 'application/json'],
            body: \json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );
        $this->assertEquals(200, $response->getStatus());

        $content = $this->getStorageData();

        $this->assertEquals('POST', $content[0]['method']);
        $this->assertNull(Uri::new($content[0]['url'])->getQuery());
        $this->assertEquals($data, $content[0]['request']);

        $this->assertContains(['name' => 'Content-Type', 'value' => 'application/json'], $content[0]['headers']);
        $this->assertContains(['name' => 'Authorization', 'value' => 'Bearer eyJhbGciOiJSUzI1NiJ9'], $content[0]['headers']);
    }

    public function testGet(): void
    {
        $response = $this->HttpClient->request(
            uri: MOCK_SERVER_URL . '/response/proxy/storage?id=100',
            headers: ['content-type' => 'application/json'],
        );
        $this->assertEquals(200, $response->getStatus());

        $content = $this->getStorageData();

        $this->assertEquals('GET', $content[0]['method']);
        $this->assertEquals('id=100', Uri::new($content[0]['url'])->getQuery());
        $this->assertEmpty($content[0]['request_payload_base64']);

        $this->assertContains(['name' => 'Content-Type', 'value' => 'application/json'], $content[0]['headers']);
        $this->assertContains(['name' => 'Authorization', 'value' => 'Bearer eyJhbGciOiJSUzI1NiJ9'], $content[0]['headers']);
    }

    private function getStorageData(): array
    {
        $url = \sprintf('%s/api/session/%s/requests', WEBHOOK_CATCHER_URL, WEBHOOK_CATCHER_SESSION_ID);
        $response = $this->HttpClient->request($url);
        $this->assertEquals(200, $response->getStatus());
        $content = $response->getBody()->buffer();
        $this->HttpClient->request($url, 'DELETE');

        $items = \json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        $items = \array_reverse($items);

        $result = [];
        foreach ($items as $item) {
            $request = \base64_decode($item['request_payload_base64'], true);
            if (\json_validate($request)) {
                $request = \json_decode($request, true, flags: JSON_THROW_ON_ERROR);
            }
            $item['request'] = $request;
            $result[] = $item;
        }
        return $result;
    }

    public function testArrayContent(): void
    {
        $response = $this->HttpClient->request(
            uri: MOCK_SERVER_URL . '/response/proxy/array-content',
            method: 'POST',
            headers: ['content-type' => 'application/json'],
        );
        $this->assertEquals(200, $response->getStatus());

        $headers = $response->getHeaders();
        $this->assertArrayHasKey('x-wh-request-id', $headers);

        $content = $this->getStorageData();

        $this->assertEquals('POST', $content[0]['method']);
        $this->assertEquals('n=1', Uri::new($content[0]['url'])->getQuery());

        $payload = $content[0]['request'];
        $this->assertCount(6, $payload);

        $expected = ['id' => 3, 'name' => 'name 3'];
        $this->assertEquals($expected, $payload[2]);

        $this->assertContains(['name' => 'Content-Type', 'value' => 'application/json'], $content[0]['headers']);
    }

    public function testStringContent(): void
    {
        $response = $this->HttpClient->request(
            uri: MOCK_SERVER_URL . '/response/proxy/string-content',
            method: 'POST',
            headers: ['content-type' => 'application/json'],
            body: '{"name": "Company"}',
        );
        $this->assertEquals(200, $response->getStatus());

        $headers = $response->getHeaders();
        $this->assertArrayHasKey('x-wh-request-id', $headers);

        $content = $this->getStorageData();

        $this->assertEquals('POST', $content[0]['method']);
        $this->assertNull(Uri::new($content[0]['url'])->getQuery());

        $this->assertEquals(['id' => 100], $content[0]['request']);

        $this->assertContains(['name' => 'Content-Type', 'value' => 'application/json'], $content[0]['headers']);
    }

    public function testFakerContent(): void
    {
        $response = $this->HttpClient->request(
            uri: MOCK_SERVER_URL . '/response/proxy/faker-content',
            method: 'POST',
            headers: ['content-type' => 'application/json'],
            body: '{"name": "Company"}',
        );
        $this->assertEquals(200, $response->getStatus());

        $headers = $response->getHeaders();
        $this->assertArrayHasKey('x-wh-request-id', $headers);

        $content = $this->getStorageData();

        $this->assertEquals('POST', $content[0]['method']);
        $this->assertNull(Uri::new($content[0]['url'])->getQuery());

        $payload = $content[0]['request'];
        $this->assertTrue(isset($payload['company']['id']));

        $uuidPattern = '~^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$~';
        $this->assertMatchesRegularExpression($uuidPattern, $payload['company']['id']);

        $this->assertContains(['name' => 'Content-Type', 'value' => 'application/json'], $content[0]['headers']);
    }

    public function testFormData(): void
    {
        $data = [
            'id' => '100',
            'item' => ['1', '2', '3'],
        ];

        $form = new Form();
        foreach ($data as $name => $value) {
            if (\is_array($value)) {
                foreach ($value as $val) {
                    $form->addField("{$name}[]", (string)$val);
                }
            } else {
                $form->addField($name, (string)$value);
            }
        }

        $response = $this->HttpClient->request(
            uri: MOCK_SERVER_URL . '/response/proxy/storage',
            method: 'POST',
            headers: ['content-type' => $form->getContentType()],
            body: $form->getContent()->read(),
        );
        $this->assertEquals(200, $response->getStatus());

        $content = $this->getStorageData();

        $this->assertEquals('POST', $content[0]['method']);
        $this->assertNull(Uri::new($content[0]['url'])->getQuery());

        \parse_str($content[0]['request'], $payload);
        $this->assertEquals($data, $payload);

        $this->assertContains(['name' => 'Content-Type', 'value' => 'application/x-www-form-urlencoded'], $content[0]['headers']);
        $this->assertContains(['name' => 'Authorization', 'value' => 'Bearer eyJhbGciOiJSUzI1NiJ9'], $content[0]['headers']);
    }
}
