<?php

namespace lav45\MockServer\mock;

use lav45\MockServer\components\DTObject;

/**
 * Class Mock
 * @package lav45\MockServer\mock
 */
class Mock extends DTObject
{
    /** @var MockRequest */
    private $request;
    /** @var MockResponse */
    private $response;
    /** @var MockWebhook */
    private $webhook;

    /**
     * @return MockRequest
     */
    public function getRequest(): MockRequest
    {
        return $this->request ??= new MockRequest();
    }

    /**
     * @param array $request
     */
    public function setRequest(array $request)
    {
        $this->request = new MockRequest($request);
    }

    /**
     * @return MockResponse
     */
    public function getResponse(): MockResponse
    {
        return $this->response ??= new MockResponse();
    }

    /**
     * @param array $response
     */
    public function setResponse(array $response)
    {
        $this->response = new MockResponse($response);
    }

    /**
     * @return MockWebhook
     */
    public function getWebhook(): MockWebhook
    {
        return $this->webhook ??= new MockWebhook();
    }

    /**
     * @param array $webhook
     */
    public function setWebhook(array $webhook)
    {
        $this->webhook = new MockWebhook($webhook);
    }
}
