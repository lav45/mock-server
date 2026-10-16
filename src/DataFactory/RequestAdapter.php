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
        $result = [];
        $bracketPairs = [];
        foreach ($this->request->getQueryParameters() as $key => $values) {
            if (\str_contains($key, '[') === false) {
                $result[$key] = $this->normalizeValue($values);
                continue;
            }
            $encodedKey = \rawurlencode($key);
            \parse_str($encodedKey, $slot);
            $result += $slot;
            foreach ($values as $value) {
                $bracketPairs[] = $encodedKey . '=' . \rawurlencode($value);
            }
        }
        $bracketQuery = \implode('&', $bracketPairs);
        \parse_str($bracketQuery, $bracketValues);
        return \array_replace($result, $bracketValues);
    }

    public function getHeaders(): array
    {
        return $this->normalizeValues(
            $this->request->getHeaders(),
        );
    }

    public function getData(): array
    {
        $body = $this->request->getBody();
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
            $result[$key] = $this->normalizeValue($value);
        }
        return $result;
    }

    /**
     * @param list<string> $values
     * @return string|list<string>
     */
    private function normalizeValue(array $values): string|array
    {
        if (isset($values[1])) {
            return $values;
        }
        return $values[0];
    }
}
