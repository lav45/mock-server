<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Functional\Components;

use Lav45\MockServer\Engine\HttpClient;

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

    public function clear(): void
    {
        $this->httpClient->request($this->url(), 'DELETE');
    }

    private function url(): string
    {
        return \sprintf('%s/api/session/%s/requests', WEBHOOK_CATCHER_URL, WEBHOOK_CATCHER_SESSION_ID);
    }
}
