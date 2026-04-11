<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Functional\Suite\Mock;

use Amp\Http\Server\FormParser;
use Lav45\MockServer\Responder\HttpClient\Factory as HttpClientFactory;
use Lav45\MockServer\Responder\HttpClient\HttpClientInterface;
use League\Uri\Uri;
use PHPUnit\Framework\TestCase;

use function Amp\delay;

class WebHookTest extends TestCase
{
    private HttpClientInterface $HttpClient;

    protected function setUp(): void
    {
        $this->HttpClient = new HttpClientFactory()->create();
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
            uri: MOCK_SERVER_URL . '/webhook/200?id=500',
            method: 'POST',
            body: \json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            headers: ['content-type' => 'application/json'],
        );
        $this->assertEquals(200, $response->getStatus());

        delay(1);

        $webhooks = $this->getStorageData();
        $webhooks = \array_reverse($webhooks);

        $this->assertEquals('POST', $webhooks[0]['method']);
        $this->assertNull(Uri::new($webhooks[0]['url'])->getQuery());
        $this->assertEmpty(\base64_decode($webhooks[0]['request_payload_base64'], true));

        $delay = $webhooks[1]['captured_at_unix_milli'] - $webhooks[0]['captured_at_unix_milli'] / 1000;
        $delay = \round($delay, 2);
        $this->assertTrue($delay >= 0.5, \var_export($delay, true));

        $this->assertEquals('POST', $webhooks[1]['method']);
        $this->assertNull(Uri::new($webhooks[1]['url'])->getQuery());

        $payload = \json_decode(\base64_decode($webhooks[1]['request_payload_base64'], true), true);
        $this->assertEquals(['text' => 'Hello world'], $payload);

        $this->assertEquals('POST', $webhooks[2]['method']);
        $this->assertSame('id=300', Uri::new($webhooks[2]['url'])->getQuery());

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
        $payload = \json_decode(\base64_decode($webhooks[2]['request_payload_base64'], true), true);
        $this->assertSame($expected, $payload);

        $this->assertContains(['name' => 'Content-Type', 'value' => 'application/json'], $webhooks[2]['headers']);

        $this->assertEquals('PUT', $webhooks[3]['method']);
        $this->assertNull(Uri::new($webhooks[3]['url'])->getQuery());
        $payload = \json_decode(\base64_decode($webhooks[3]['request_payload_base64'], true), true);
        $this->assertCount(4, $payload);

        $this->assertArrayHasKey('id', $payload[0]);
        $uuidPattern = '~^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$~';
        $this->assertMatchesRegularExpression($uuidPattern, $payload[0]['id']);
        $this->assertArrayHasKey('name', $payload[0]);

        $this->assertContains(['name' => 'Content-Type', 'value' => 'application/json'], $webhooks[3]['headers']);
        $this->assertContains(['name' => 'X-Api-Token', 'value' => 'e71ad173-dacf-493c-be55-643074fdf41c'], $webhooks[3]['headers']);

        $this->assertEquals('GET', $webhooks[4]['method']);
        $this->assertEquals('sss=get', Uri::new($webhooks[4]['url'])->getQuery());
        $this->assertEmpty(\base64_decode($webhooks[4]['request_payload_base64'], true));
        $this->assertContains(['name' => 'X-Api-Token', 'value' => 'e71ad173-dacf-493c-be55-643074fdf41c'], $webhooks[4]['headers']);

        $this->assertEquals('DELETE', $webhooks[5]['method']);
        $this->assertNull(Uri::new($webhooks[5]['url'])->getQuery());
        $this->assertEmpty(\base64_decode($webhooks[5]['request_payload_base64'], true));
        $this->assertContains(['name' => 'X-Api-Token', 'value' => 'e71ad173-dacf-493c-be55-643074fdf41c'], $webhooks[5]['headers']);

        $this->assertEquals('POST', $webhooks[6]['method']);
        $this->assertContains(['name' => 'Content-Type', 'value' => 'application/x-www-form-urlencoded'], $webhooks[6]['headers']);
        \parse_str(\base64_decode($webhooks[6]['request_payload_base64'], true), $payload);
        $this->assertSame(['name' => 'John', 'age' => '12'], $payload);

        $this->assertEquals('POST', $webhooks[7]['method']);
        $this->assertContains(['name' => 'Content-Type', 'value' => 'multipart/form-data; boundary=FB'], $webhooks[7]['headers']);

        $body = \base64_decode($webhooks[7]['request_payload_base64'], true);
        $boundary = FormParser\parseContentBoundary('multipart/form-data; boundary=FB');
        $payload = new FormParser\FormParser()->parseBody($body, $boundary)->getValues();
        $this->assertSame(['name' => ['John'], 'age' => ['12']], $payload);

        $this->assertEquals('POST', $webhooks[8]['method']);
        $this->assertContains(['name' => 'Content-Type', 'value' => 'application/json'], $webhooks[8]['headers']);
        $this->assertContains(['name' => 'X-Api-Token', 'value' => 'e71ad173-dacf-493c-be55-643074fdf41c'], $webhooks[8]['headers']);

        $payload = \json_decode(\base64_decode($webhooks[8]['request_payload_base64'], true), true);
        $this->assertMatchesRegularExpression($uuidPattern, $payload['uuid']);
        $this->assertMatchesRegularExpression('~^TEST\d{4}$~', $payload['id']);
        $this->assertMatchesRegularExpression('~^\d{12}$~', $payload['correlationId']);

        $this->assertEquals('POST', $webhooks[9]['method']);
        $payload = \json_decode(\base64_decode($webhooks[9]['request_payload_base64'], true), true);
        $this->assertSame(['text' => 'OK'], $payload);
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
}
