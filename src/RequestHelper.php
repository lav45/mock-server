<?php

namespace lav45\MockServer;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
use Amp\Http\Server\ClientException;
use Amp\Http\Server\FormParser;
use Exception;
use lav45\MockServer\Request\WrappedRequest;

/**
 * Class RequestHelper
 * @package lav45\MockServer
 */
class RequestHelper
{
    /** @var WrappedRequest */
    private WrappedRequest $wrappedRequest;
    /** @var array */
    private $get;

    /**
     * @param WrappedRequest $request
     * @throws Exception
     */
    public function __construct(WrappedRequest $request)
    {
        $this->wrappedRequest = $request;
    }

    /**
     * @return array
     */
    public function getUrlParams()
    {
        return $this->wrappedRequest->getAttribute(Router::class);
    }

    /**
     * @return array|string|null
     */
    public function get($key = null, $default = null)
    {
        if ($this->get === null) {
            parse_str($this->wrappedRequest->getUri()->getQuery(), $this->get);
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
        return $this->wrappedRequest->getBody()->buffer();
    }

    /**
     * @return bool
     */
    public function isFormData()
    {
        $contentType = $this->wrappedRequest->getHeader('content-type') ?? '';
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
        $request = $this->wrappedRequest->getRequest();
        return FormParser\parseForm($request)->getValues();
    }
}