<?php declare(strict_types=1);

namespace lav45\MockServer\Request\Wrapper;

use Amp\Http\Client\BufferedContent;
use Amp\Http\Client\Form;
use Amp\Http\Client\HttpContent;
use Amp\Http\Server\FormParser;
use Amp\Http\Server\Request;
use lav45\MockServer\Reactor;
use RuntimeException;

/**
 * @mixin Request
 */
final class RequestWrapper
{
    private string|null $body = null;
    private array|null $get = null;

    public function __construct(private readonly Request $request)
    {
    }

    public function __call(string $name, array $args)
    {
        if (method_exists($this->request, $name)) {
            return call_user_func_array([$this->request, $name], $args);
        }
        throw new RuntimeException('Calling unknown method: ' . get_class($this) . "::{$name}()");
    }

    public function getUrlParams(): array
    {
        return $this->request->getAttribute(Reactor::class);
    }

    public function get(string $key = null, mixed $default = null): mixed
    {
        if ($this->get === null) {
            $this->get = self::parseQuery($this->request->getUri()->getQuery());
        }
        if ($key === null) {
            return $this->get;
        }
        return $this->get[$key] ?? $default;
    }

    public static function parseQuery(string $query): array
    {
        parse_str($query, $parseQuery);
        return $parseQuery;
    }

    public function post(): array
    {
        return $this->isFormData() ?
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
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        }
        return [];
    }

    public function body(): string
    {
        return $this->body ??= $this->request->getBody()->read() ?? '';
    }

    public function isFormData(): bool
    {
        return FormParser\parseContentBoundary($this->getContentType()) !== null;
    }

    private function getContentType(): string
    {
        return $this->request->getHeader('content-type') ?? '';
    }

    private function getFormValues(): array
    {
        return FormParser\Form::fromRequest($this->request)->getValues();
    }

    private function getFormContent(): Form
    {
        $form = new Form();
        foreach ($this->parseForm() as $name => $value) {
            $form->addField($name, $value);
        }
        return $form;
    }

    private function getBodyContent(): BufferedContent
    {
        return BufferedContent::fromString($this->body(), $this->getContentType());
    }

    private function hasContent(): bool
    {
        return in_array($this->request->getMethod(), ['POST', 'PUT', 'PATCH'], true);
    }

    public function getContent(): HttpContent|null
    {
        if ($this->hasContent() === false) {
            return null;
        }
        return $this->isFormData() ?
            $this->getFormContent() :
            $this->getBodyContent();
    }
}
