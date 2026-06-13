<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\DataFactory;

use Amp\Http\Server\Request;
use Lav45\MockServer\DataFactory\CollectionFactory;
use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\Parser\InlineParser;
use Lav45\MockServer\Parser\ParamParser;
use Lav45\MockServer\Parser\VariableParser;
use Lav45\MockServer\Test\Unit\Components\FakeHttpDriverClient;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

final class CollectionFactoryTest extends TestCase
{
    private function createRequest(string $url = 'https://localhost/'): Request
    {
        return new Request(new FakeHttpDriverClient(), 'GET', Http::new($url));
    }

    private function createParser(): VariableParser
    {
        return new ParamParser(new class implements InlineParser {
            public function replace(mixed $data): mixed
            {
                return $data;
            }
        });
    }

    private function decodeBody(string $json): mixed
    {
        return \json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    }

    public function testHasMatchesDataType(): void
    {
        $this->assertTrue(new CollectionFactory(new DataBuilder())->has(['type' => 'data']));
    }

    public function testHasDoesNotMatchWhenTypeMissing(): void
    {
        $this->assertFalse(new CollectionFactory(new DataBuilder())->has([]));
    }

    public function testHasDoesNotMatchOtherType(): void
    {
        $this->assertFalse(new CollectionFactory(new DataBuilder())->has(['type' => 'content']));
    }

    public function testCreateWithDefaultPagination(): void
    {
        $items = [['id' => 1], ['id' => 2], ['id' => 3]];

        $response = new CollectionFactory(new DataBuilder())->create(
            $this->createRequest(),
            $this->createParser(),
            ['items' => $items],
        );

        $this->assertSame(200, $response->status->value);
        $this->assertSame('application/json', $response->headers->toArray()['content-type']);
        $this->assertSame($items, $this->decodeBody($response->body->value));
    }

    public function testCreateWithPagination(): void
    {
        $items = [['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4], ['id' => 5]];

        $response = new CollectionFactory(new DataBuilder())->create(
            $this->createRequest('https://localhost/?page=2&per-page=2'),
            $this->createParser(),
            ['items' => $items],
        );

        $this->assertSame([['id' => 3], ['id' => 4]], $this->decodeBody($response->body->value));
    }

    public function testCreateWithOutOfRangePage(): void
    {
        $response = new CollectionFactory(new DataBuilder())->create(
            $this->createRequest('https://localhost/?page=99'),
            $this->createParser(),
            ['items' => [['id' => 1], ['id' => 2]]],
        );

        $this->assertSame([], $this->decodeBody($response->body->value));
    }

    public function testCreateWithCustomPaginationParams(): void
    {
        $items = [['id' => 1], ['id' => 2], ['id' => 3]];

        $response = new CollectionFactory(new DataBuilder())->create(
            $this->createRequest('https://localhost/?p=2&size=2'),
            $this->createParser(),
            [
                'items' => $items,
                'pagination' => [
                    'pageParam' => 'p',
                    'pageSizeParam' => 'size',
                ],
            ],
        );

        $this->assertSame([['id' => 3]], $this->decodeBody($response->body->value));
    }

    public function testCreateWithCustomResult(): void
    {
        $customResult = [['custom' => true]];

        $response = new CollectionFactory(new DataBuilder())->create(
            $this->createRequest(),
            $this->createParser(),
            [
                'items' => [['id' => 1]],
                'result' => $customResult,
            ],
        );

        $this->assertSame($customResult, $this->decodeBody($response->body->value));
    }

    public function testCreateWithPaginationHeaders(): void
    {
        $items = [['id' => 1], ['id' => 2], ['id' => 3]];

        $response = new CollectionFactory(new DataBuilder())->create(
            $this->createRequest(),
            $this->createParser(),
            [
                'items' => $items,
                'headers' => [
                    'X-Pagination-Total-Count' => '{{response.pagination.totalItems}}',
                    'X-Pagination-Current-Page' => '{{response.pagination.currentPage}}',
                    'X-Pagination-Page-Count' => '{{response.pagination.totalPages}}',
                    'X-Pagination-Per-Page' => '{{response.pagination.pageSize}}',
                ],
            ],
        );

        $headers = $response->headers->toArray();
        $this->assertEquals(3, $headers['X-Pagination-Total-Count']);
        $this->assertEquals(1, $headers['X-Pagination-Current-Page']);
        $this->assertEquals(1, $headers['X-Pagination-Page-Count']);
        $this->assertEquals(3, $headers['X-Pagination-Per-Page']);
    }

    public function testCreateWithResultStructure(): void
    {
        $items = [['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4], ['id' => 5]];

        $response = new CollectionFactory(new DataBuilder())->create(
            $this->createRequest('https://localhost/?_p=2&_s=2'),
            $this->createParser(),
            [
                'items' => $items,
                'pagination' => [
                    'pageParam' => '_p',
                    'pageSizeParam' => '_s',
                ],
                'result' => [
                    'data' => '{{response.items}}',
                    'pagination' => '{{response.pagination}}',
                ],
            ],
        );

        $content = $this->decodeBody($response->body->value);
        $this->assertSame([['id' => 3], ['id' => 4]], $content['data']);
        $this->assertSame([
            'totalItems' => 5,
            'currentPage' => 2,
            'totalPages' => 3,
            'pageSize' => 2,
        ], $content['pagination']);
    }

    public function testCreateWithInlineInterpolationInResult(): void
    {
        $items = [['id' => 1], ['id' => 2], ['id' => 3]];

        $response = new CollectionFactory(new DataBuilder())->create(
            $this->createRequest(),
            $this->createParser(),
            [
                'items' => $items,
                'result' => [
                    'data' => '{{response.items}}',
                    'info' => [
                        'X-Pagination-Total-Count' => '{response.pagination.totalItems}',
                        'X-Pagination-Current-Page' => '{response.pagination.currentPage}',
                        'X-Pagination-Page-Count' => '{response.pagination.totalPages}',
                        'X-Pagination-Per-Page' => '{response.pagination.pageSize}',
                    ],
                ],
            ],
        );

        $content = $this->decodeBody($response->body->value);
        $this->assertSame($items, $content['data']);
        $this->assertEquals(3, $content['info']['X-Pagination-Total-Count']);
        $this->assertEquals(1, $content['info']['X-Pagination-Current-Page']);
        $this->assertEquals(1, $content['info']['X-Pagination-Page-Count']);
        $this->assertEquals(3, $content['info']['X-Pagination-Per-Page']);
    }
}
