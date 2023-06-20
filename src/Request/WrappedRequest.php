<?php

namespace lav45\MockServer\Request;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
use Amp\Http\Server\ClientException;
use Amp\Http\Server\Request;
use RuntimeException;

/**
 * @mixin Request
 */
class WrappedRequest
{
    /** @var string */
    protected const ATTRIBUTE_NAME = __CLASS__;
    /** @var Request */
    protected Request $request;
    /** @var WrappedRequestBody|null */
    protected ?WrappedRequestBody $body = null;

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
        throw new RuntimeException('Calling unknown method: ' . get_class($this) . "::$name()");
    }

    /**
     * @return WrappedRequestBody
     */
    public function getBody(): WrappedRequestBody
    {
        return $this->body ??= new WrappedRequestBody($this->request->getBody());
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
        $clone->setBody($this->getBody()->buffer());
        return $clone;
    }
}
