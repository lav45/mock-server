<?php

namespace lav45\MockServer\Request;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
use Amp\Http\Server\ClientException;
use Amp\Http\Server\FormParser;
use Amp\Http\Server\Request;
use lav45\MockServer\Router;
use RuntimeException;

/**
 * @mixin Request
 */
class RequestWrapper
{
    /** @var string */
    protected const ATTRIBUTE_NAME = __CLASS__;
    /** @var Request */
    protected Request $request;
    /** @var RequestBodyWrapper|null */
    protected ?RequestBodyWrapper $body = null;
    /** @var array|null */
    private ?array $get = null;

    /**
     * @param Request $request
     * @return static
     */
    public static function getInstance(Request $request): static
    {
        if ($request->hasAttribute(self::ATTRIBUTE_NAME) === false) {
            $request->setAttribute(self::ATTRIBUTE_NAME, new self($request));
        }
        return $request->getAttribute(self::ATTRIBUTE_NAME);
    }

    /**
     * @param Request $request
     */
    protected function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param string $name
     * @param array $args
     * @return false|mixed
     */
    public function __call(string $name, array $args)
    {
        if (method_exists($this->request, $name)) {
            return call_user_func_array([$this->request, $name], $args);
        }
        throw new RuntimeException('Calling unknown method: ' . get_class($this) . "::{$name}()");
    }

    /**
     * @return RequestBodyWrapper
     */
    public function getBody(): RequestBodyWrapper
    {
        return $this->body ??= new RequestBodyWrapper($this->request->getBody());
    }

    /**
     * @return Request
     * @throws BufferException
     * @throws StreamException
     * @throws ClientException
     */
    public function getRequest(): Request
    {
        $clone = clone $this->request;
        $clone->setBody($this->getBody()->read());
        return $clone;
    }

    /**
     * @return array
     */
    public function getUrlParams()
    {
        return $this->getAttribute(Router::class);
    }

    /**
     * @return array|string|null
     */
    public function get($key = null, $default = null)
    {
        if ($this->get === null) {
            parse_str($this->getUri()->getQuery(), $this->get);
        }
        if ($key === null) {
            return $this->get;
        }
        return $this->get[$key] ?? $default;
    }

    /**
     * @return array
     * @throws BufferException
     * @throws StreamException
     * @throws ClientException
     */
    public function post()
    {
        return $this->isFormData() ?
            $this->parseForm() :
            $this->parseBody();
    }

    /**
     * @return array
     * @throws BufferException
     * @throws ClientException
     * @throws StreamException
     */
    protected function parseForm()
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

    /**
     * @return array
     * @throws BufferException
     * @throws StreamException
     * @throws ClientException
     */
    protected function parseBody()
    {
        return json_decode($this->body(), true);
    }

    /**
     * @return string
     * @throws BufferException
     * @throws StreamException
     * @throws ClientException
     */
    public function body()
    {
        return $this->getBody()->read();
    }

    /**
     * @return bool
     */
    public function isFormData()
    {
        $contentType = $this->getHeader('content-type') ?? '';
        $boundary = FormParser\parseContentBoundary($contentType);
        return $boundary !== null;
    }

    /**
     * @return string[][]
     * @throws BufferException
     * @throws ClientException
     * @throws StreamException
     */
    private function getFormValues(): array
    {
        return FormParser\parseForm($this->getRequest())->getValues();
    }
}
