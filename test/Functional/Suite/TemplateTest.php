<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Functional\Suite;

use Lav45\MockServer\Driver\HttpClientFactory;
use Lav45\MockServer\Engine\HttpClient;
use Lav45\MockServer\Test\Functional\Components\WebHookStorage;
use League\Uri\Uri;
use PHPUnit\Framework\TestCase;

use function Amp\delay;

class TemplateTest extends TestCase
{
    private const string API_KEY = 'e71ad173-dacf-493c-be55-643074fdf41c';
    private const string CHANNEL = 'c70d6033-7edc-49f8-b609-e361739e994e';
    private const string UUID_PATTERN = '~^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$~';

    private HttpClient $HttpClient;

    private WebHookStorage $webHookStorage;

    protected function setUp(): void
    {
        $this->HttpClient = new HttpClientFactory()->create();
        $this->webHookStorage = new WebHookStorage($this->HttpClient);
    }

    public function testResponseAndWebhook(): void
    {
        $requestId = '11111111-2222-3333-4444-555555555555';

        $response = $this->HttpClient->request(
            uri: MOCK_SERVER_URL . '/v1/order',
            method: 'POST',
            headers: [
                'content-type' => 'application/json',
                'x-request-id' => $requestId,
            ],
        );

        $this->assertEquals(200, $response->getStatus());
        $this->assertSame('application/json', $response->getHeaders()['content-type'][0]);

        $body = \json_decode($response->getBody()->stream->read(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertMatchesRegularExpression(self::UUID_PATTERN, $body['id']);
        $this->assertIsInt($body['createdAt']);

        delay(0.2);

        $webhooks = $this->webHookStorage->getData();
        $this->assertCount(1, $webhooks);
        $webhook = $webhooks[0];

        $this->assertEquals('POST', $webhook['method']);
        $this->assertStringContainsString('/centrifugo/api/publish', Uri::new($webhook['url'])->getPath());

        $headers = $this->headersMap($webhook['headers']);
        $this->assertSame(self::API_KEY, $headers['x-api-key']);
        $this->assertSame('transport', $headers['x-centrifugo-error-mode']);
        $this->assertSame($requestId, $headers['x-request-id']);

        $payload = $webhook['request'];
        $this->assertSame(self::CHANNEL, $payload['channel']);
        $this->assertSame('create_order', $payload['data']['type']);
        $this->assertSame($body, $payload['data']['data']);
    }

    public function testContentTemplate(): void
    {
        $response = $this->HttpClient->request(MOCK_SERVER_URL . '/v1/status');

        $this->assertEquals(200, $response->getStatus());
        $this->assertSame('application/json', $response->getHeaders()['content-type'][0]);

        $body = \json_decode($response->getBody()->stream->read(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(['status' => 'ok', 'code' => 200], $body);
    }

    /**
     * @param list<array{name: string, value: string}> $headers
     * @return array<string, string>
     */
    private function headersMap(array $headers): array
    {
        $result = [];
        foreach ($headers as $header) {
            $result[\strtolower($header['name'])] = $header['value'];
        }
        return $result;
    }
}
