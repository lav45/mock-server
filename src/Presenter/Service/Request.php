<?php declare(strict_types=1);

namespace Lav45\MockServer\Presenter\Service;

use Amp\Http\Server\FormParser;
use Amp\Http\Server\Request as HttpRequest;

final class Request
{
    public string $body {
        get {
            return $this->body ??= $this->request->getBody()->buffer();
        }
    }

    public array $query {
        get {
            return $this->query ??= $this->parseQuery($this->request->getUri()->getQuery());
        }
    }

    public function __construct(
        private readonly HttpRequest $request,
    ) {}

    private function parseQuery(string|null $query): array
    {
        if (empty($query)) {
            return [];
        }
        \parse_str($query, $parseQuery);
        return $parseQuery;
    }

    public function getData(): array
    {
        return $this->parseContentBoundary() !== null
            ? $this->parseForm()
            : $this->parseBody();
    }

    private function parseForm(): array
    {
        $result = [];
        foreach ($this->getFormValues() as $key => $value) {
            if (isset($value[1])) {
                $result[$key] = $value;
            } else {
                $result[$key] = $value[0];
            }
        }
        return $result;
    }

    private function parseBody(): array
    {
        if (\json_validate($this->body)) {
            return \json_decode($this->body, true, 512, JSON_THROW_ON_ERROR);
        }
        return [];
    }

    private function parseContentBoundary(): string|null
    {
        return FormParser\parseContentBoundary($this->getContentType());
    }

    private function getContentType(): string
    {
        return $this->request->getHeader('content-type') ?? '';
    }

    private function getFormValues(): array
    {
        return new FormParser\FormParser()
            ->parseBody($this->body, $this->parseContentBoundary())
            ->getValues();
    }
}
