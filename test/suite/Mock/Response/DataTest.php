<?php declare(strict_types=1);

namespace lav45\MockServer\test\suite\Mock\Response;

use lav45\MockServer\HttpClient;
use PHPUnit\Framework\TestCase;

/**
 * @see \lav45\MockServer\Mock\Response\Data
 */
class DataTest extends TestCase
{
    private HttpClient $HttpClient;

    protected function setUp(): void
    {
        $this->HttpClient = (new HttpClient())->build();
    }

    public function testIndex(): void
    {
        $response = $this->HttpClient->request('http://127.0.0.1/response/data');
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('[]', $response->getBody()->buffer());

        $headers = $response->getHeaders();
        $this->assertArrayHasKey('content-type', $headers);
        $this->assertEquals('application/json', $headers['content-type'][0]);
    }

    public function testJson(): void
    {
        $response = $this->HttpClient->request('http://127.0.0.1/response/data/json');
        $this->assertEquals(200, $response->getStatus());

        $content = $response->getBody()->buffer();
        $content = json_decode($content, true);

        $this->assertArrayHasKey('data', $content);
        $this->assertCount(6, $content['data']);

        $expected = ['id' => 3, 'name' => 'name 3'];
        $this->assertEquals($expected, $content['data'][2]);

        $this->assertArrayHasKey('pagination', $content);
        $expectedPagination = [
            'totalItems' => 12,
            'currentPage' => 1,
            'totalPages' => 2,
            'pageSize' => 6,
        ];
        $this->assertEquals($expectedPagination, $content['pagination']);

        $this->assertArrayHasKey('info', $content);
        $expected = [
            'X-Pagination-Total-Count' => $expectedPagination['totalItems'],
            'X-Pagination-Current-Page' => $expectedPagination['currentPage'],
            'X-Pagination-Page-Count' => $expectedPagination['totalPages'],
            'X-Pagination-Per-Page' => $expectedPagination['pageSize'],
        ];
        $this->assertEquals($expected, $content['info']);

        $this->assertHeaders($response->getHeaders(), [
            'content-type' => 'application/json',
            'x-pagination-total-count' => $expectedPagination['totalItems'],
            'x-pagination-current-page' => $expectedPagination['currentPage'],
            'x-pagination-page-count' => $expectedPagination['totalPages'],
            'x-pagination-per-page' => $expectedPagination['pageSize'],
        ]);
    }

    public function testJsonPage2(): void
    {
        $response = $this->HttpClient->request('http://127.0.0.1/response/data/json', query: ['_p' => 2]);
        $this->assertEquals(200, $response->getStatus());

        $content = $response->getBody()->buffer();
        $content = json_decode($content, true);

        $this->assertArrayHasKey('data', $content);
        $this->assertCount(6, $content['data']);

        $uuidPattern = '~^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$~i';
        $this->assertMatchesRegularExpression($uuidPattern, $content['data'][0]['id']);
        $this->assertArrayHasKey('name', $content['data'][0]);

        $this->assertArrayHasKey('pagination', $content);
        $expectedPagination = [
            'totalItems' => 12,
            'currentPage' => 2,
            'totalPages' => 2,
            'pageSize' => 6,
        ];
        $this->assertEquals($expectedPagination, $content['pagination']);

        $this->assertArrayHasKey('info', $content);
        $expected = [
            'X-Pagination-Total-Count' => $expectedPagination['totalItems'],
            'X-Pagination-Current-Page' => $expectedPagination['currentPage'],
            'X-Pagination-Page-Count' => $expectedPagination['totalPages'],
            'X-Pagination-Per-Page' => $expectedPagination['pageSize'],
        ];
        $this->assertEquals($expected, $content['info']);

        $this->assertHeaders($response->getHeaders(), [
            'content-type' => 'application/json',
            'x-pagination-total-count' => $expectedPagination['totalItems'],
            'x-pagination-current-page' => $expectedPagination['currentPage'],
            'x-pagination-page-count' => $expectedPagination['totalPages'],
            'x-pagination-per-page' => $expectedPagination['pageSize'],
        ]);
    }

    public function testJsonPage0(): void
    {
        $response = $this->HttpClient->request('http://127.0.0.1/response/data/json', query: ['_p' => 0]);
        $this->assertEquals(200, $response->getStatus());

        $content = $response->getBody()->buffer();
        $content = json_decode($content, true);

        $this->assertArrayHasKey('data', $content);
        $this->assertCount(6, $content['data']);

        $this->assertArrayHasKey('pagination', $content);
        $expectedPagination = [
            'totalItems' => 12,
            'currentPage' => 1,
            'totalPages' => 2,
            'pageSize' => 6,
        ];
        $this->assertEquals($expectedPagination, $content['pagination']);

        $this->assertArrayHasKey('info', $content);
        $expected = [
            'X-Pagination-Total-Count' => $expectedPagination['totalItems'],
            'X-Pagination-Current-Page' => $expectedPagination['currentPage'],
            'X-Pagination-Page-Count' => $expectedPagination['totalPages'],
            'X-Pagination-Per-Page' => $expectedPagination['pageSize'],
        ];
        $this->assertEquals($expected, $content['info']);

        $this->assertHeaders($response->getHeaders(), [
            'content-type' => 'application/json',
            'x-pagination-total-count' => $expectedPagination['totalItems'],
            'x-pagination-current-page' => $expectedPagination['currentPage'],
            'x-pagination-page-count' => $expectedPagination['totalPages'],
            'x-pagination-per-page' => $expectedPagination['pageSize'],
        ]);
    }

    public function testJsonSize3(): void
    {
        $response = $this->HttpClient->request('http://127.0.0.1/response/data/json', query: ['_p' => 2, '_s' => 3]);
        $this->assertEquals(200, $response->getStatus());

        $content = $response->getBody()->buffer();
        $content = json_decode($content, true);

        $this->assertArrayHasKey('data', $content);
        $this->assertCount(3, $content['data']);

        $expected = ['id' => 4, 'name' => 'name 4'];
        $this->assertEquals($expected, $content['data'][0]);

        $this->assertArrayHasKey('pagination', $content);
        $expectedPagination = [
            'totalItems' => 12,
            'currentPage' => 2,
            'totalPages' => 4,
            'pageSize' => 3,
        ];
        $this->assertEquals($expectedPagination, $content['pagination']);

        $this->assertArrayHasKey('info', $content);
        $expected = [
            'X-Pagination-Total-Count' => $expectedPagination['totalItems'],
            'X-Pagination-Current-Page' => $expectedPagination['currentPage'],
            'X-Pagination-Page-Count' => $expectedPagination['totalPages'],
            'X-Pagination-Per-Page' => $expectedPagination['pageSize'],
        ];
        $this->assertEquals($expected, $content['info']);

        $this->assertHeaders($response->getHeaders(), [
            'content-type' => 'application/json',
            'x-pagination-total-count' => $expectedPagination['totalItems'],
            'x-pagination-current-page' => $expectedPagination['currentPage'],
            'x-pagination-page-count' => $expectedPagination['totalPages'],
            'x-pagination-per-page' => $expectedPagination['pageSize'],
        ]);
    }

    public function testJsonSize10(): void
    {
        $response = $this->HttpClient->request('http://127.0.0.1/response/data/json', query: ['_p' => 2, '_s' => 10]);
        $this->assertEquals(200, $response->getStatus());

        $content = $response->getBody()->buffer();
        $content = json_decode($content, true);

        $this->assertArrayHasKey('data', $content);
        $this->assertCount(2, $content['data']);

        $this->assertArrayHasKey('pagination', $content);
        $expected = [
            'totalItems' => 12,
            'currentPage' => 2,
            'totalPages' => 2,
            'pageSize' => 2,
        ];
        $this->assertEquals($expected, $content['pagination']);
    }

    protected function assertHeaders(array $headers, array $expected): void
    {
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $headers);
            $this->assertEquals($value, $headers[$key][0]);
        }
    }

    public function testFile(): void
    {
        $response = $this->HttpClient->request('http://127.0.0.1/response/data/file', query: ['_p' => 2, '_s' => 5]);
        $this->assertEquals(200, $response->getStatus());

        $content = $response->getBody()->buffer();
        $content = json_decode($content, true);

        $this->assertArrayHasKey('data', $content);
        $this->assertCount(5, $content['data']);

        $uuidPattern = '~^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$~i';
        $this->assertMatchesRegularExpression($uuidPattern, $content['data'][1]['id']);
        $this->assertArrayHasKey('name', $content['data'][1]);

        $this->assertArrayHasKey('pagination', $content);
        $expectedPagination = [
            'totalItems' => 30,
            'currentPage' => 2,
            'totalPages' => 6,
            'pageSize' => 5,
        ];
        $this->assertEquals($expectedPagination, $content['pagination']);

        $this->assertArrayHasKey('info', $content);
        $expected = [
            'X-Pagination-Total-Count' => $expectedPagination['totalItems'],
            'X-Pagination-Current-Page' => $expectedPagination['currentPage'],
            'X-Pagination-Page-Count' => $expectedPagination['totalPages'],
            'X-Pagination-Per-Page' => $expectedPagination['pageSize'],
        ];
        $this->assertEquals($expected, $content['info']);

        $this->assertHeaders($response->getHeaders(), [
            'content-type' => 'application/json',
            'x-pagination-total-count' => $expectedPagination['totalItems'],
            'x-pagination-current-page' => $expectedPagination['currentPage'],
            'x-pagination-page-count' => $expectedPagination['totalPages'],
            'x-pagination-per-page' => $expectedPagination['pageSize'],
        ]);
    }

    public function testStatus(): void
    {
        $response = $this->HttpClient->request('http://127.0.0.1/response/data/status');
        $this->assertEquals(418, $response->getStatus());
        $this->assertEquals('[]', $response->getBody()->buffer());
    }
}