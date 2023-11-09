<?php declare(strict_types=1);

namespace lav45\MockServer\Request;

use Amp\Http\Server\FormParser;
use Amp\Http\Server\Request;
use lav45\MockServer\Reactor;
use RuntimeException;

/**
 * @mixin Request
 */
class RequestWrapper
{
    protected const ATTRIBUTE_NAME = __CLASS__;
    protected Request $request;
    protected ?RequestBodyWrapper $body = null;
    private ?array $get = null;

    public static function getInstance(Request $request): static
    {
        if ($request->hasAttribute(self::ATTRIBUTE_NAME) === false) {
            $request->setAttribute(self::ATTRIBUTE_NAME, new self($request));
        }
        return $request->getAttribute(self::ATTRIBUTE_NAME);
    }

    protected function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function __call(string $name, array $args)
    {
        if (method_exists($this->request, $name)) {
            return call_user_func_array([$this->request, $name], $args);
        }
        throw new RuntimeException('Calling unknown method: ' . get_class($this) . "::{$name}()");
    }

    public function getBody(): RequestBodyWrapper
    {
        return $this->body ??= new RequestBodyWrapper($this->request->getBody());
    }

    public function getRequest(): Request
    {
        $clone = clone $this->request;
        $clone->setBody($this->getBody()->read());
        return $clone;
    }

    public function getUrlParams(): array
    {
        return $this->getAttribute(Reactor::class);
    }

    public function get(string $key = null, array|int|string|null $default = null): array|int|string|null
    {
        if ($this->get === null) {
            parse_str($this->getUri()->getQuery(), $this->get);
        }
        if ($key === null) {
            return $this->get;
        }
        return $this->get[$key] ?? $default;
    }

    public function post(): array
    {
        return $this->isFormData() ?
            $this->parseForm() :
            $this->parseBody();
    }

    public function parseForm(): array
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

    protected function parseBody(): array
    {
        if ($body = $this->body()) {
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        }
        return [];
    }

    public function body(): string
    {
        return $this->getBody()->read();
    }

    public function isFormData(): bool
    {
        $contentType = $this->getHeader('content-type') ?? '';
        $boundary = FormParser\parseContentBoundary($contentType);
        return $boundary !== null;
    }

    private function getFormValues(): array
    {
        return FormParser\parseForm($this->getRequest())->getValues();
    }
}