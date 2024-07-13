<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Wrapper;

use Amp\Http\Server\FormParser;
use Amp\Http\Server\Request as HttpRequest;
use RuntimeException;

/**
 * @mixin HttpRequest
 */
final class Request
{
    public const string URL_PARAMS = 'URL_PARAMS';

    private string $body;
    private array $get;

    public function __construct(private readonly HttpRequest $request) {}

    public function __call(string $name, array $args)
    {
        if (\method_exists($this->request, $name)) {
            return \call_user_func_array([$this->request, $name], $args);
        }
        throw new RuntimeException('Calling unknown method: ' . self::class . "::{$name}()");
    }

    public function getUrlParams(): array
    {
        return $this->request->getAttribute(self::URL_PARAMS);
    }

    public function get(): array
    {
        return $this->get ??= $this->parseQuery($this->request->getUri()->getQuery());
    }

    private function parseQuery(string|null $query): array
    {
        if (empty($query)) {
            return [];
        }
        \parse_str($query, $parseQuery);
        return $parseQuery;
    }

    public function post(): array
    {
        return $this->parseContentBoundary() !== null ?
            $this->parseForm() :
            $this->parseBody();
    }

    private function parseForm(): array
    {
        $result = [];
        $data = $this->getFormValues();
        foreach ($data as $key => $value) {
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
        if ($body = $this->body()) {
            return \json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        }
        return [];
    }

    public function body(): string
    {
        return $this->body ??= $this->request->getBody()->buffer();
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
        return (new FormParser\FormParser())
            ->parseBody($this->body(), $this->parseContentBoundary())
            ->getValues();
    }
}
