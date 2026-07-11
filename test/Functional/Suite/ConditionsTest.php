<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Functional\Suite;

use Lav45\MockServer\Driver\HttpClientFactory;
use Lav45\MockServer\Engine\HttpClient;
use Lav45\MockServer\Test\Functional\Components\WebHookStorage;
use League\Uri\Uri;
use PHPUnit\Framework\TestCase;

use function Amp\delay;

class ConditionsTest extends TestCase
{
    private HttpClient $HttpClient;

    private WebHookStorage $webHookStorage;

    protected function setUp(): void
    {
        $this->HttpClient = new HttpClientFactory()->create();
        $this->webHookStorage = new WebHookStorage($this->HttpClient);
    }

    /**
     * @return array{status: int, body: mixed}
     */
    private function postJson(string $uri, array $body, array $headers = []): array
    {
        $response = $this->HttpClient->request(
            uri: $uri,
            method: 'POST',
            headers: ['content-type' => 'application/json'] + $headers,
            body: \json_encode($body, JSON_THROW_ON_ERROR),
        );
        return [
            'status' => $response->getStatus(),
            'body' => \json_decode($response->getBody()->stream->read(), true, flags: JSON_THROW_ON_ERROR),
        ];
    }

    // --- payment route: and / or / not / comparison / regex / presence ---

    public function testLimitExceeded(): void
    {
        // and: amount > env.limit, currency in [...], x-role != premium
        $result = $this->postJson(
            MOCK_SERVER_URL . '/conditions/payment/p-1',
            ['amount' => 1500, 'currency' => 'USD', 'note' => 'hi'],
            ['x-role' => 'user'],
        );
        $this->assertSame(402, $result['status']);
        $this->assertSame(['result' => 'limit_exceeded'], $result['body']);
    }

    public function testFirstMatchWins(): void
    {
        // condition 1 (limit) and condition 4 (empty note) both match — the first one wins
        $result = $this->postJson(
            MOCK_SERVER_URL . '/conditions/payment/p-1',
            ['amount' => 1500, 'currency' => 'EUR', 'note' => ''],
            ['x-role' => 'user'],
        );
        $this->assertSame(402, $result['status']);
        $this->assertSame(['result' => 'limit_exceeded'], $result['body']);
    }

    public function testPremiumRoleBypassesLimit(): void
    {
        // != premium fails, so the limit condition is skipped
        $result = $this->postJson(
            MOCK_SERVER_URL . '/conditions/payment/p-1',
            ['amount' => 1500, 'currency' => 'USD', 'note' => 'hi'],
            ['x-role' => 'premium'],
        );
        $this->assertSame(200, $result['status']);
        $this->assertSame(['result' => 'ok', 'id' => 'p-1'], $result['body']);
    }

    public function testDryRunViaQuery(): void
    {
        // or: request.query.dry_run = "true"
        $result = $this->postJson(
            MOCK_SERVER_URL . '/conditions/payment/p-1?dry_run=true',
            ['amount' => 10, 'currency' => 'USD', 'note' => 'hi'],
        );
        $this->assertSame(200, $result['status']);
        $this->assertSame(['result' => 'dry_run'], $result['body']);
    }

    public function testDryRunViaHeader(): void
    {
        // or: exists request.headers.x-dry-run
        $result = $this->postJson(
            MOCK_SERVER_URL . '/conditions/payment/p-1',
            ['amount' => 10, 'currency' => 'USD', 'note' => 'hi'],
            ['x-dry-run' => '1'],
        );
        $this->assertSame(200, $result['status']);
        $this->assertSame(['result' => 'dry_run'], $result['body']);
    }

    public function testRegexParam(): void
    {
        // ~: request.params.id matches ^test-
        $result = $this->postJson(
            MOCK_SERVER_URL . '/conditions/payment/test-42',
            ['amount' => 10, 'currency' => 'USD', 'note' => 'hi'],
        );
        $this->assertSame(200, $result['status']);
        $this->assertSame(['result' => 'test_mode'], $result['body']);
    }

    public function testEmptyNote(): void
    {
        // empty: request.body.note is an empty string
        $result = $this->postJson(
            MOCK_SERVER_URL . '/conditions/payment/p-1',
            ['amount' => 10, 'currency' => 'USD', 'note' => ''],
        );
        $this->assertSame(422, $result['status']);
        $this->assertSame(['result' => 'note_required'], $result['body']);
    }

    public function testFallbackWhenNoConditionMatches(): void
    {
        $result = $this->postJson(
            MOCK_SERVER_URL . '/conditions/payment/p-1',
            ['amount' => 10, 'currency' => 'USD', 'note' => 'present'],
        );
        $this->assertSame(200, $result['status']);
        $this->assertSame(['result' => 'ok', 'id' => 'p-1'], $result['body']);
    }

    // --- operators route: >= / < / <= / contains / not (reached in order) ---

    public function testOperatorGte(): void
    {
        $result = $this->postJson(MOCK_SERVER_URL . '/conditions/operators', ['score' => 10]);
        $this->assertSame(['op' => 'gte'], $result['body']);
    }

    public function testOperatorLt(): void
    {
        $result = $this->postJson(MOCK_SERVER_URL . '/conditions/operators', ['low' => 3]);
        $this->assertSame(['op' => 'lt'], $result['body']);
    }

    public function testOperatorLte(): void
    {
        $result = $this->postJson(MOCK_SERVER_URL . '/conditions/operators', ['high' => 100]);
        $this->assertSame(['op' => 'lte'], $result['body']);
    }

    public function testOperatorContains(): void
    {
        $result = $this->postJson(MOCK_SERVER_URL . '/conditions/operators', ['roles' => ['admin', 'user']]);
        $this->assertSame(['op' => 'contains'], $result['body']);
    }

    public function testOperatorNot(): void
    {
        $result = $this->postJson(MOCK_SERVER_URL . '/conditions/operators', ['stage' => 'dev']);
        $this->assertSame(['op' => 'not'], $result['body']);
    }

    public function testOperatorNoneMatches(): void
    {
        $result = $this->postJson(MOCK_SERVER_URL . '/conditions/operators', ['stage' => 'prod']);
        $this->assertSame(['op' => 'none'], $result['body']);
    }

    // --- webhooks override / suppression ---

    public function testWebhookOverride(): void
    {
        $this->webHookStorage->clear();

        $result = $this->postJson(MOCK_SERVER_URL . '/conditions/webhooks/override', ['ping' => true]);
        $this->assertSame(['result' => 'override'], $result['body']);

        delay(1);
        $this->assertSame(['case=override'], $this->capturedCases());
    }

    public function testWebhookSuppress(): void
    {
        $this->webHookStorage->clear();

        $result = $this->postJson(MOCK_SERVER_URL . '/conditions/webhooks/suppress', ['ping' => true]);
        $this->assertSame(['result' => 'suppress'], $result['body']);

        delay(1);
        $this->assertSame([], $this->capturedCases());
    }

    public function testWebhookFallback(): void
    {
        $this->webHookStorage->clear();

        $result = $this->postJson(MOCK_SERVER_URL . '/conditions/webhooks/other', ['ping' => true]);
        $this->assertSame(['result' => 'fallback'], $result['body']);

        delay(1);
        $this->assertSame(['case=fallback'], $this->capturedCases());
    }

    /**
     * Query strings (`case=...`) of webhooks captured for the conditions/webhooks route.
     *
     * @return string[]
     */
    private function capturedCases(): array
    {
        $cases = [];
        foreach ($this->webHookStorage->getData() as $webhook) {
            $query = Uri::new($webhook['url'])->getQuery();
            if ($query !== null && \str_starts_with($query, 'case=')) {
                $cases[] = $query;
            }
        }
        return $cases;
    }
}
