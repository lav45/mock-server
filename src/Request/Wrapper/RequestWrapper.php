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
class RequestWrapper
{
    protected Request $request;
    protected RequestBodyWrapper|null $body = null;
    private array|null $get = null;

    public function __construct(Request $request)
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

    public function getBodyWrapper(): RequestBodyWrapper
    {
        return $this->body ??= new RequestBodyWrapper($this->request->getBody());
    }

    public function getRequest(): Request
    {
        $clone = clone $this->request;
        $clone->setBody($this->getBodyWrapper()->read());
        return $clone;
    }

    public function getUrlParams(): array
    {
        return $this->getAttribute(Reactor::class);
    }

    public function get(string $key = null, array|int|float|string|bool|null $default = null): array|int|float|string|bool|null
    {
        if ($this->get === null) {
            $this->get = self::parseQuery($this->getUri()->getQuery());
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

    protected function parseForm(): array
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
        return $this->getBodyWrapper()->read();
    }

    public function isFormData(): bool
    {
        $contentType = $this->getHeader('content-type') ?? '';
        $boundary = FormParser\parseContentBoundary($contentType);
        return $boundary !== null;
    }

    private function getFormValues(): array
    {
        return FormParser\Form::fromRequest($this->getRequest())->getValues();
    }

    protected function getFormContent(): Form
    {
        $form = new Form();
        foreach ($this->parseForm() as $name => $value) {
            $form->addField($name, $value);
        }
        return $form;
    }

    protected function getBodyContent(): BufferedContent
    {
        return BufferedContent::fromString($this->body(), $this->getHeader('content-type'));
    }

    public function getContent(): HttpContent|null
    {
        if (in_array($this->getMethod(), ['POST', 'PUT', 'PATCH'], true) === false) {
            return null;
        }
        return $this->isFormData() ?
            $this->getFormContent() :
            $this->getBodyContent();
    }
}
