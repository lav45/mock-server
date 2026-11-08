<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Functional\Components;

use Lav45\MockServer\Engine\HttpClient;
use PHPUnit\Framework\Assert;

final readonly class WebHookStorage
{
    public function __construct(
        private HttpClient $httpClient,
    ) {}

    public function getData(): array
    {
        $content = $this->httpClient->request($this->url())->getBody()->stream->read();
        $this->clear();

        $items = \json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        $items = \array_reverse($items);

        $result = [];
        foreach ($items as $item) {
            $request = \base64_decode($item['request_payload_base64'], true);
            if (\json_validate($request)) {
                $request = \json_decode($request, true, flags: JSON_THROW_ON_ERROR);
            }
            $item['request'] = $request;
            $result[] = $item;
        }
        return $result;
    }

    /**
     * @param \Closure(array): bool ...$matchers
     * @return list<array>
     */
    public function getDataInOrder(\Closure ...$matchers): array
    {
        $items = $this->getData();
        Assert::assertCount(\count($matchers), $items);

        $result = [];
        $capturedAt = 0;
        foreach ($matchers as $position => $matcher) {
            $found = \array_values(
                \array_filter($items, $matcher),
            );
            Assert::assertCount(1, $found, "Webhook #{$position}");

            $item = $found[0];
            Assert::assertGreaterThanOrEqual($capturedAt, $item['captured_at_unix_milli'], "Webhook #{$position}");
            $capturedAt = $item['captured_at_unix_milli'];
            $result[] = $item;
        }
        return $result;
    }

    public function clear(): void
    {
        $this->httpClient->request($this->url(), 'DELETE');
    }

    private function url(): string
    {
        return \sprintf('%s/api/session/%s/requests', WEBHOOK_CATCHER_URL, WEBHOOK_CATCHER_SESSION_ID);
    }
}
