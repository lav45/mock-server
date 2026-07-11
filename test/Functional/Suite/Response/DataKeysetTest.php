<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Functional\Suite\Response;

use Lav45\MockServer\Driver\HttpClientFactory;
use Lav45\MockServer\Engine\HttpClient;
use PHPUnit\Framework\TestCase;

class DataKeysetTest extends TestCase
{
    private HttpClient $HttpClient;

    protected function setUp(): void
    {
        $this->HttpClient = new HttpClientFactory()->create();
    }

    private function get(string $url): array
    {
        $response = $this->HttpClient->request(MOCK_SERVER_URL . $url);
        $this->assertEquals(200, $response->getStatus());

        $content = $response->getBody()->stream->read();
        return \json_decode($content, true, flags: JSON_THROW_ON_ERROR);
    }

    private function ids(array $content): array
    {
        return \array_column($content['data'], 'id');
    }

    private function headers(string $url): array
    {
        $response = $this->HttpClient->request(MOCK_SERVER_URL . $url);
        $this->assertEquals(200, $response->getStatus());

        $result = [];
        foreach ($response->getHeaders() as $name => $values) {
            $result[$name] = $values[0];
        }
        return $result;
    }

    public function testFirstPage(): void
    {
        $content = $this->get('/response/data/keyset');

        $this->assertEquals(['e', 'f'], $this->ids($content));
        $this->assertEquals([
            'next' => 'f',
            'prev' => null,
            'hasNext' => true,
            'hasPrev' => false,
            'pageSize' => 2,
        ], $content['pagination']);
    }

    public function testAfter(): void
    {
        $content = $this->get('/response/data/keyset?after=f&limit=2');

        $this->assertEquals(['d', 'b'], $this->ids($content));
        $this->assertEquals([
            'next' => 'b',
            'prev' => 'd',
            'hasNext' => true,
            'hasPrev' => true,
            'pageSize' => 2,
        ], $content['pagination']);
    }

    public function testBefore(): void
    {
        $content = $this->get('/response/data/keyset?before=d&limit=2');

        $this->assertEquals(['e', 'f'], $this->ids($content));
        $this->assertEquals([
            'next' => 'f',
            'prev' => null,
            'hasNext' => true,
            'hasPrev' => false,
            'pageSize' => 2,
        ], $content['pagination']);
    }

    public function testLastPartialPage(): void
    {
        $content = $this->get('/response/data/keyset?after=c&limit=2');

        $this->assertEquals(['a'], $this->ids($content));
        $this->assertEquals([
            'next' => null,
            'prev' => 'a',
            'hasNext' => false,
            'hasPrev' => true,
            'pageSize' => 1,
        ], $content['pagination']);
    }

    public function testUnknownCursorFallsBackToFirstPage(): void
    {
        $content = $this->get('/response/data/keyset?after=zzz&limit=2');

        $this->assertEquals(['e', 'f'], $this->ids($content));
        $this->assertEquals([
            'next' => 'f',
            'prev' => null,
            'hasNext' => true,
            'hasPrev' => false,
            'pageSize' => 2,
        ], $content['pagination']);
    }

    public function testLimit(): void
    {
        $content = $this->get('/response/data/keyset?limit=3');

        $this->assertEquals(['e', 'f', 'd'], $this->ids($content));
        $this->assertEquals('d', $content['pagination']['next']);
        $this->assertEquals(3, $content['pagination']['pageSize']);
    }

    public function testHeadersFirstPage(): void
    {
        $headers = $this->headers('/response/data/keyset-headers');

        $this->assertEquals('true', $headers['x-has-next']);
        $this->assertEquals('false', $headers['x-has-prev']);
        $this->assertEquals('f', $headers['x-next-cursor']);
        $this->assertEquals('2', $headers['x-page-size']);
    }

    public function testHeadersMiddlePage(): void
    {
        $headers = $this->headers('/response/data/keyset-headers?after=f&limit=2');

        $this->assertEquals('true', $headers['x-has-next']);
        $this->assertEquals('true', $headers['x-has-prev']);
        $this->assertEquals('b', $headers['x-next-cursor']);
        $this->assertEquals('d', $headers['x-prev-cursor']);
        $this->assertEquals('2', $headers['x-page-size']);
    }

    public function testHeadersLastPage(): void
    {
        $headers = $this->headers('/response/data/keyset-headers?after=c&limit=2');

        $this->assertEquals('false', $headers['x-has-next']);
        $this->assertEquals('true', $headers['x-has-prev']);
        $this->assertEquals('a', $headers['x-prev-cursor']);
        $this->assertEquals('1', $headers['x-page-size']);
    }

    public function testCustomParamsAndPrimaryKey(): void
    {
        $content = $this->get('/response/data/keyset-config?size=1');

        $this->assertEquals(['u2'], \array_column($content['data'], 'uuid'));
        $this->assertEquals([
            'next' => 'u2',
            'prev' => null,
            'hasNext' => true,
            'hasPrev' => false,
            'pageSize' => 1,
        ], $content['pagination']);

        $content = $this->get('/response/data/keyset-config?cursor=u2&size=1');

        $this->assertEquals(['u3'], \array_column($content['data'], 'uuid'));
        $this->assertEquals('Bob', $content['data'][0]['name']);
        $this->assertTrue($content['pagination']['hasPrev']);
    }
}
