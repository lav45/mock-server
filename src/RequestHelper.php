<?php

namespace lav45\MockServer;

use Amp\Http\Server\FormParser;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestBody;
use Exception;
use Yiisoft\Cache\ArrayCache;

/**
 * Class RequestHelper
 * @package lav45\MockServer
 */
class RequestHelper
{
    /** @var string */
    protected const REQUEST_ID_KEY = 'requestId';

    /** @var Request */
    private Request $request;
    /** @var ArrayCache */
    private static $cache;
    /** @var array */
    private $get;
    /** @var string */
    private $body;
    /** @var array */
    private $formValues;

    /**
     * @param Request $request
     * @throws Exception
     */
    public function __construct(Request $request)
    {
        $requestId = $this->getRequestId($request);

        // Create new RequestBody to avoid exceptions "Can't buffer() a payload more than once"
        $bodyString = $this->getBodyString($requestId, $request->getBody());
        $body = new RequestBody($bodyString);
        $this->request = clone $request;
        $this->request->setBody($body);
    }

    /**
     * @param Request $request
     * @return string
     * @throws Exception
     */
    protected function getRequestId(Request $request): string
    {
        if ($request->hasAttribute(self::REQUEST_ID_KEY) === false) {
            $request->setAttribute(self::REQUEST_ID_KEY, bin2hex(random_bytes(16)));
        }
        return $request->getAttribute(self::REQUEST_ID_KEY);
    }

    /**
     * @param string $requestId
     * @param RequestBody $body
     * @return string
     * @throws Exception
     */
    protected function getBodyString(string $requestId, RequestBody $body)
    {
        $fnGetBodyString = static function () use ($requestId, $body) {
            $result = $body->buffer();
            $body->onClose(static function () use ($requestId) {
                self::getCache()->delete($requestId);
            });
            return $result;
        };
        $cache = self::getCache();
        if ($cache->has($requestId) === false) {
            $cache->set($requestId, $fnGetBodyString());
        }
        return $cache->get($requestId);
    }

    /**
     * @return ArrayCache
     */
    protected static function getCache(): ArrayCache
    {
        return self::$cache ??= new ArrayCache();
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