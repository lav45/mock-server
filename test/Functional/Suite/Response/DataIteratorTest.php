<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Functional\Suite\Response;

use Lav45\MockServer\Driver\HttpClientFactory;
use Lav45\MockServer\Engine\HttpClient;
use PHPUnit\Framework\TestCase;

class DataIteratorTest extends TestCase
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

    public function testFirstPage(): void
    {
        $content = $this->get('/response/data/iterator');

        $this->assertEquals([60, 50], $this->ids($content));
        $this->assertEquals([
            'next' => '50',
            'prev' => null,
            'hasNext' => true,
            'hasPrev' => false,
            'pageSize' => 2,
        ], $content['pagination']);
    }

    public function testForward(): void
    {
        $content = $this->get('/response/data/iterator?iterator=50&limit=2');

        $this->assertEquals([40, 30], $this->ids($content));
        $this->assertEquals([
            'next' => '30',
            'prev' => '-40',
            'hasNext' => true,
            'hasPrev' => true,
            'pageSize' => 2,
        ], $content['pagination']);
    }

    public function testBackward(): void
    {
        $content = $this->get('/response/data/iterator?iterator=-40&limit=2');

        $this->assertEquals([60, 50], $this->ids($content));
        $this->assertEquals([
            'next' => '50',
            'prev' => null,
            'hasNext' => true,
            'hasPrev' => false,
            'pageSize' => 2,
        ], $content['pagination']);
    }

    public function testLastPartialPage(): void
    {
        $content = $this->get('/response/data/iterator?iterator=20&limit=2');

        $this->assertEquals([10], $this->ids($content));
        $this->assertEquals([
            'next' => null,
            'prev' => '-10',
            'hasNext' => false,
            'hasPrev' => true,
            'pageSize' => 1,
        ], $content['pagination']);
    }

    public function testUnknownCursorFallsBackToFirstPage(): void
    {
        $content = $this->get('/response/data/iterator?iterator=999&limit=2');

        $this->assertEquals([60, 50], $this->ids($content));
        $this->assertFalse($content['pagination']['hasPrev']);
    }

    public function testCustomParams(): void
    {
        $content = $this->get('/response/data/iterator-config?cursor=20&size=2');

        $this->assertEquals([30, 40], $this->ids($content));
        $this->assertEquals([
            'next' => '40',
            'prev' => '-30',
            'hasNext' => true,
            'hasPrev' => true,
            'pageSize' => 2,
        ], $content['pagination']);
    }
}
