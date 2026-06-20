<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\DataFactory;

use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\DataFactory\WebHooksFactory;
use Lav45\MockServer\Domain\WebHooks;
use PHPUnit\Framework\TestCase;

final class WebHooksFactoryTest extends TestCase
{
    private function create(array $webhooks): WebHooks
    {
        return new WebHooksFactory(new DataBuilder())->create(['webhooks' => $webhooks]);
    }

    private function decodeBody(string $json): mixed
    {
        return \json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    }

    // --- has() ---

    public function testHasMatchesWhenWebhooksKeyPresent(): void
    {
        $this->assertTrue(new WebHooksFactory(new DataBuilder())->has(['webhooks' => []]));
    }

    public function testHasDoesNotMatchWhenWebhooksKeyMissing(): void
    {
        $this->assertFalse(new WebHooksFactory(new DataBuilder())->has([]));
    }

    // --- Collection ---

    public function testCreateWithEmptyDataReturnsEmptyWebHooks(): void
    {
        $webHooks = $this->create([]);

        $this->assertCount(0, $webHooks->items);
    }

    public function testCreateWithMultipleItemsReturnsAllWebHooks(): void
    {
        $data = [
            ['url' => 'https://a.example.com'],
            ['url' => 'https://b.example.com'],
            ['url' => 'https://c.example.com'],
        ];
        $webHooks = $this->create($data);

        $this->assertCount(3, $webHooks->items);
    }

    // --- Method ---

    public function testCreateDefaultsToPostMethodWhenNotSpecified(): void
    {
        $webHooks = $this->create([
            ['url' => 'https://example.com'],
        ]);

        $this->assertSame('POST', $webHooks->items[0]->method->value);
    }

    public function testCreateWithExplicitMethod(): void
    {
        $data = [
            ['method' => 'PUT', 'url' => 'https://example.com'],
            ['method' => 'GET', 'url' => 'https://example.com'],
            ['method' => 'DELETE', 'url' => 'https://example.com'],
        ];
        $webHooks = $this->create($data);

        $this->assertSame('PUT', $webHooks->items[0]->method->value);
        $this->assertSame('GET', $webHooks->items[1]->method->value);
        $this->assertSame('DELETE', $webHooks->items[2]->method->value);
    }

    // --- Delay ---

    public function testCreateDefaultsToZeroDelay(): void
    {
        $webHooks = $this->create([
            ['url' => 'https://example.com'],
        ]);

        $this->assertSame(0.0, $webHooks->items[0]->delay->value);
    }

    public function testCreateWithExplicitDelay(): void
    {
        $webHooks = $this->create([
            ['delay' => 0.5, 'url' => 'https://example.com'],
        ]);

        $this->assertSame(0.5, $webHooks->items[0]->delay->value);
    }

    // --- URL ---

    public function testCreateBuildsUrlFromData(): void
    {
        $webHooks = $this->create([
            ['url' => 'https://example.com/hook?id=300'],
        ]);

        $this->assertSame('https://example.com/hook?id=300', $webHooks->items[0]->url->value);
    }

    // --- JSON body ---

    public function testCreateWithJsonBodyEncodesArrayToJson(): void
    {
        $webHooks = $this->create([
            [
                'url' => 'https://example.com',
                'body' => ['id' => 1, 'name' => 'test'],
            ],
        ]);

        $this->assertSame(['id' => 1, 'name' => 'test'], $this->decodeBody($webHooks->items[0]->body->toString()));
    }

    public function testCreateWithJsonBodySetsContentTypeHeader(): void
    {
        $webHooks = $this->create([
            [
                'url' => 'https://example.com',
                'headers' => ['content-type' => 'application/json'],
                'body' => ['id' => 1],
            ],
        ]);

        $this->assertSame('application/json', $webHooks->items[0]->headers->toArray()['content-type']);
    }

    public function testCreateWithJsonArrayBody(): void
    {
        $items = [['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]];
        $webHooks = $this->create([
            [
                'url' => 'https://example.com',
                'body' => $items,
            ],
        ]);

        $this->assertSame($items, $this->decodeBody($webHooks->items[0]->body->toString()));
    }

    public function testCreateWithJsonStringBodySetsContentTypeHeader(): void
    {
        $webHooks = $this->create([
            [
                'url' => 'https://example.com',
                'body' => '{"text": "Hello world"}',
            ],
        ]);

        $this->assertSame('application/json', $webHooks->items[0]->headers->toArray()['content-type']);
    }

    // --- Text body ---

    public function testCreateWithTextBody(): void
    {
        $webHooks = $this->create([
            [
                'url' => 'https://example.com',
                'body' => '{"text": "Hello world"}',
            ],
        ]);

        $this->assertSame('{"text": "Hello world"}', $webHooks->items[0]->body->toString());
    }

    public function testCreateWithNoBodyKeyDefaultsToEmptyBody(): void
    {
        $webHooks = $this->create([
            ['url' => 'https://example.com'],
        ]);

        $this->assertSame('', $webHooks->items[0]->body->toString());
    }

    public function testCreateWithTextBodyDoesNotSetContentTypeHeader(): void
    {
        $webHooks = $this->create([
            ['url' => 'https://example.com', 'body' => 'hello'],
        ]);

        $this->assertArrayNotHasKey('content-type', $webHooks->items[0]->headers->toArray());
    }

    // --- Headers ---

    public function testCreateWithCustomHeaders(): void
    {
        $webHooks = $this->create([
            [
                'url' => 'https://example.com',
                'headers' => ['X-Api-Token' => 'e71ad173-dacf-493c-be55-643074fdf41c'],
            ],
        ]);

        $this->assertSame('e71ad173-dacf-493c-be55-643074fdf41c', $webHooks->items[0]->headers->toArray()['X-Api-Token']);
    }

    public function testCreateWithJsonBodyAndCustomHeadersMergesContentType(): void
    {
        $webHooks = $this->create([
            [
                'url' => 'https://example.com',
                'headers' => [
                    'content-type' => 'application/json',
                    'X-Api-Token' => 'token123',
                ],
                'body' => ['id' => 1],
            ],
        ]);

        $headers = $webHooks->items[0]->headers->toArray();
        $this->assertSame('application/json', $headers['content-type']);
        $this->assertSame('token123', $headers['X-Api-Token']);
    }

    public function testCreateWithExplicitContentTypeAndTextBody(): void
    {
        $webHooks = $this->create([
            [
                'url' => 'https://example.com',
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body' => 'name=John&age=12',
            ],
        ]);

        $this->assertSame('application/x-www-form-urlencoded', $webHooks->items[0]->headers->toArray()['Content-Type']);
        $this->assertSame('name=John&age=12', $webHooks->items[0]->body->toString());
    }
}
