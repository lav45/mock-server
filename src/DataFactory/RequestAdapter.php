<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory;

use Lav45\MockServer\Engine\Http\ServerRequest;

final readonly class RequestAdapter
{
    public function __construct(
        private ServerRequest $request,
    ) {}

    public function getQuery(): array
    {
        return $this->normalizeValues(
            $this->request->getQueryParameters(),
        );
    }

    public function getHeaders(): array
    {
        return $this->normalizeValues(
            $this->request->getHeaders(),
        );
    }

    public function getBody(): string
    {
        return $this->request->getBody();
    }

    public function getData(): array
    {
        $body = $this->getBody();
        if (empty($body)) {
            return [];
        }
        if (\json_validate($body)) {
            return \json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        }
        $parsed = $this->request->getParsedBody();
        if ($parsed !== []) {
            return $parsed;
        }
        \parse_str($body, $result);
        return $result;
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
}
