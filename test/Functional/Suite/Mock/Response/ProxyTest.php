<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Functional\Suite\Mock\Response;

use Amp\Http\Client\Form;
use Lav45\MockServer\Infrastructure\HttpClient\Factory as HttpClientFactory;
use Lav45\MockServer\Infrastructure\HttpClient\HttpClientInterface;
use League\Uri\Uri;
use PHPUnit\Framework\TestCase;

class ProxyTest extends TestCase
{
    private HttpClientInterface $HttpClient;

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
            body: \json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            headers: ['content-type' => 'application/json'],
        );
        $this->assertEquals(200, $response->getStatus());

        $content = $this->getStorageData();

        $this->assertEquals('POST', $content[0]['method']);
        $this->assertNull(Uri::new($content[0]['url'])->getQuery());
        $this->assertEquals($data, \json_decode(\base64_decode($content[0]['request_payload_base64'], true), true));

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
        $this->assertNull(\json_decode(\base64_decode($content[0]['request_payload_base64'], true), true));

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
        return \json_decode($content, true, flags: JSON_THROW_ON_ERROR);
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

        $payload = \json_decode(\base64_decode($content[0]['request_payload_base64'], true), true);
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
            body: '{"name": "Company"}',
            headers: ['content-type' => 'application/json'],
        );
        $this->assertEquals(200, $response->getStatus());

        $headers = $response->getHeaders();
        $this->assertArrayHasKey('x-wh-request-id', $headers);

        $content = $this->getStorageData();

        $this->assertEquals('POST', $content[0]['method']);
        $this->assertNull(Uri::new($content[0]['url'])->getQuery());

        $payload = \json_decode(\base64_decode($content[0]['request_payload_base64'], true), true);
        $this->assertEquals(['id' => 100], $payload);

        $this->assertContains(['name' => 'Content-Type', 'value' => 'application/json'], $content[0]['headers']);
    }

    public function testFakerContent(): void
    {
        $response = $this->HttpClient->request(
            uri: MOCK_SERVER_URL . '/response/proxy/faker-content',
            method: 'POST',
            body: '{"name": "Company"}',
            headers: ['content-type' => 'application/json'],
        );
        $this->assertEquals(200, $response->getStatus());

        $headers = $response->getHeaders();
        $this->assertArrayHasKey('x-wh-request-id', $headers);

        $content = $this->getStorageData();

        $this->assertEquals('POST', $content[0]['method']);
        $this->assertNull(Uri::new($content[0]['url'])->getQuery());

        $payload = \json_decode(\base64_decode($content[0]['request_payload_base64'], true), true);
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
            body: $form->getContent()->read(),
            headers: ['content-type' => $form->getContentType()],
        );
        $this->assertEquals(200, $response->getStatus());

        $content = $this->getStorageData();

        $this->assertEquals('POST', $content[0]['method']);
        $this->assertNull(Uri::new($content[0]['url'])->getQuery());

        \parse_str(\base64_decode($content[0]['request_payload_base64'], true), $payload);
        $this->assertEquals($data, $payload);

        $this->assertContains(['name' => 'Content-Type', 'value' => 'application/x-www-form-urlencoded'], $content[0]['headers']);
        $this->assertContains(['name' => 'Authorization', 'value' => 'Bearer eyJhbGciOiJSUzI1NiJ9'], $content[0]['headers']);
    }
}
