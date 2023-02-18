<?php

namespace lav45\MockServer;

use Amp\Http\Server\FormParser;
use Amp\Http\Server\Request;

/**
 * Class RequestHelper
 * @package lav45\MockServer
 */
class RequestHelper
{
    /** @var array */
    private $get;

    /**
     * @param Request $request
     */
    public function __construct(private Request $request)
    {
    }

    /**
     * @return array
     */
    public function getUrlParams()
    {
        return $this->request->getAttribute(Router::class);
    }

    /**
     * @return array|string|null
     */
    public function get($key = null, $default = null)
    {
        if ($this->get === null) {
            parse_str($this->request->getUri()->getQuery(), $this->get);
        }
        if ($key === null) {
            return $this->get;
        }
        return $this->get[$key] ?? $default;
    }

    /**
     * @return array
     * @throws \Amp\ByteStream\BufferException
     * @throws \Amp\ByteStream\StreamException
     * @throws \Amp\Http\Server\ClientException
     */
    public function post()
    {
        return $this->isFormData() ?
            $this->parseForm() :
            $this->parceBody();
    }

    /**
     * @return array
     */
    protected function parseForm()
    {
        $result = [];
        $data = FormParser\parseForm($this->request)->getValues();
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
     * @throws \Amp\ByteStream\BufferException
     * @throws \Amp\ByteStream\StreamException
     * @throws \Amp\Http\Server\ClientException
     */
    protected function parceBody()
    {
        return json_decode($this->body(), true);
    }

    /**
     * @return string
     * @throws \Amp\ByteStream\BufferException
     * @throws \Amp\ByteStream\StreamException
     * @throws \Amp\Http\Server\ClientException
     */
    public function body()
    {
        return $this->request->getBody()->buffer();
    }

    /**
     * @return bool
     */
    public function isFormData()
    {
        $contentType = $this->request->getHeader('content-type') ?? '';
        $boundary = FormParser\parseContentBoundary($contentType);
        return $boundary !== null;
    }
}