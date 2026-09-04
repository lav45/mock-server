<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Functional\Suite;

use Amp\TimeoutCancellation;
use Lav45\MockServer\Driver\HttpClientFactory;
use Lav45\MockServer\Engine\Http\ClientResponse;
use Lav45\MockServer\Engine\HttpClient;
use PHPUnit\Framework\TestCase;

use function Amp\async;

class BodySizeTest extends TestCase
{
    private int $maxRequestBodySize = 1024 * 1024;

    private HttpClient $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = new HttpClientFactory(retryLimit: 0)->create();
    }

    public function testBodyWithinTheLimitReachesTheMock(): void
    {
        $name = \str_repeat('a', $this->maxRequestBodySize - 1024);
        $body = \json_encode(['name' => $name], JSON_THROW_ON_ERROR);

        $response = $this->request('/body-size/reads-body', $body);

        self::assertSame(200, $response->getStatus());
        self::assertSame($name, $response->getBody()->stream->read());
    }

    public function testBodyOverTheLimitIsRejectedWithPayloadTooLarge(): void
    {
        $name = \str_repeat('a', $this->maxRequestBodySize * 2);
        $body = \json_encode(['name' => $name], JSON_THROW_ON_ERROR);

        $response = $this->request('/body-size/reads-body', $body);

        self::assertSame(413, $response->getStatus());
    }

    public function testBodyOverTheLimitIsRejectedWhenTheMockIgnoresTheBody(): void
    {
        $body = \str_repeat('a', $this->maxRequestBodySize * 2);

        $response = $this->request('/body-size/ignores-body', $body);

        self::assertSame(413, $response->getStatus());
    }

    private function request(string $path, string $body): ClientResponse
    {
        $request = async(
            fn(): ClientResponse => $this->httpClient->request(
                uri: MOCK_SERVER_URL . $path,
                method: 'POST',
                headers: ['content-type' => 'application/json'],
                body: $body,
            ),
        );

        return $request->await(new TimeoutCancellation(15));
    }
}
