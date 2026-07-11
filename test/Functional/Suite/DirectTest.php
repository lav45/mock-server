<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Functional\Suite;

use Lav45\MockServer\Driver\HttpClientFactory;
use Lav45\MockServer\Engine\HttpClient;
use Lav45\MockServer\Test\Functional\Components\WebHookStorage;
use PHPUnit\Framework\TestCase;

use function Amp\delay;

final class DirectTest extends TestCase
{
    private HttpClient $HttpClient;

    private WebHookStorage $webHookStorage;

    protected function setUp(): void
    {
        $this->HttpClient = new HttpClientFactory()->create();
        $this->webHookStorage = new WebHookStorage($this->HttpClient);
    }

    public function testRequest(): void
    {
        $data = [
            'items' => [1, 2, 3],
        ];

        $response = $this->HttpClient->request(
            uri: MOCK_SERVER_URL . '/direct/100?status=2',
            method: 'PUT',
            headers: ['Content-Type' => 'application/json'],
            body: \json_encode($data, JSON_THROW_ON_ERROR),
        );

        $content = $response->getBody()->stream->read();
        $content = \json_decode($content, true, flags: JSON_THROW_ON_ERROR);

        $this->assertEquals(['id' => '100'], $content['mainParams']);
        $this->assertEquals([], $content['directParams']);
        $this->assertEquals(['status' => '2'], $content['directGet']);
        $this->assertEquals($data, $content['directPost']);

        $this->assertArrayHasKey('x-status', $content['directHeaders']);
        $this->assertEquals('active', $content['directHeaders']['x-status']);
        $this->assertArrayHasKey('content-type', $content['directHeaders']);
        $this->assertEquals('application/json', $content['directHeaders']['content-type']);

        $headers = $response->getHeaders();
        $this->assertArrayHasKey('x-status', $headers);
        $this->assertEquals('open', $headers['x-status'][0]);
        $this->assertArrayHasKey('content-type', $headers);
        $this->assertEquals('application/json', $headers['content-type'][0]);

        delay(0.1);

        $webhooks = $this->webHookStorage->getData();

        $this->assertEquals('POST', $webhooks[0]['method']);
        $this->assertEmpty($webhooks[0]['request']);

        $this->assertEquals('PUT', $webhooks[1]['method']);
        $this->assertMatchesRegularExpression('~^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$~', $webhooks[1]['request']['id']);
        $this->assertNotEmpty($webhooks[1]['request']['name']);
    }

}
