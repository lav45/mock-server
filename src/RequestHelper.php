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
    /** @var string */
    protected const REQUEST_ID_KEY = 'requestId';

    /** @var Request */
    private $request;
    /** @var array */
    private $get;
    /** @var string */
    private $body;
    /** @var array */
    private $formValues;
    /** @var self[] */
    private static array $instances = [];

    /**
     * @param Request $request
     * @return RequestHelper
     * @throws \Exception
     */
    public static function getInstance(Request $request)
    {
        $requestId = self::getRequestId($request);
        return self::$instances[$requestId] ??= new self($request);
    }

    /**
     * @param Request $request
     * @return string
     * @throws \Exception
     */
    protected static function getRequestId(Request $request): string
    {
        if ($request->hasAttribute(self::REQUEST_ID_KEY) === false) {
            $request->setAttribute(self::REQUEST_ID_KEY, bin2hex(random_bytes(32)));
        }
        return $request->getAttribute(self::REQUEST_ID_KEY);
    }

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = clone $request;
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
            $this->parseBody();
    }

    /**
     * @return array
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
     * @throws \Amp\ByteStream\BufferException
     * @throws \Amp\ByteStream\StreamException
     * @throws \Amp\Http\Server\ClientException
     */
    protected function parseBody()
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
        return $this->body ??= $this->request->getBody()->read();
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

    /**
     * @return string[][]
     */
    private function getFormValues(): array
    {
        return $this->formValues ??= FormParser\parseForm($this->request)->getValues();
    }
}