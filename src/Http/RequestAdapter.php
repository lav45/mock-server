<?php declare(strict_types=1);

namespace Lav45\MockServer\Http;

use Amp\Http\Server\FormParser;
use Amp\Http\Server\Request as HttpRequest;

final class RequestAdapter
{
    public string $body {
        get {
            return $this->body ??= $this->request->getBody()->buffer();
        }
    }

    public function __construct(
        private readonly HttpRequest $request,
    ) {}

    public function getQuery(): array
    {
        return $this->normalizeValues(
            $this->request->getQueryParameters(),
        );
    }

    public function getData(): array
    {
        $body = $this->body;
        if (\json_validate($body)) {
            return \json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        }
        return $this->normalizeValues(
            $this->getFormValues($body, $this->parseContentBoundary()),
        );
    }

    private function normalizeValues(array $values): array
    {
        $result = [];
        foreach ($values as $key => $value) {
            if (isset($value[1])) {
                $result[$key] = $value;
            } else {
                $result[$key] = $value[0];
            }
        }
        return $result;
    }

    private function parseContentBoundary(): string|null
    {
        return FormParser\parseContentBoundary(
            contentType: $this->request->getHeader('content-type') ?? '',
        );
    }

    private function getFormValues(string $body, string|null $boundary): array
    {
        return new FormParser\FormParser()
            ->parseBody($body, $boundary)
            ->getValues();
    }
}
