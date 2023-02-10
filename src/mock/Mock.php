<?php

namespace lav45\MockServer\mock;

/**
 * Class Mock
 * @package lav45\MockServer
 */
class Mock extends DTObject
{
    /** @var RequestMock */
    private $request;
    /** @var ResponseMock */
    private $response;
    /** @var WebhookMock */
    private $webhook;

    /**
     * @return RequestMock
     */
    public function getRequest(): RequestMock
    {
        return $this->request ??= new RequestMock();
    }

    /**
     * @param array $request
     */
    public function setRequest(array $request)
    {
        $this->request = new RequestMock($request);
    }

    /**
     * @return ResponseMock
     */
    public function getResponse(): ResponseMock
    {
        return $this->response ??= new ResponseMock();
    }

    /**
     * @param array $response
     */
    public function setResponse(array $response)
    {
        $this->response = new ResponseMock($response);
    }

    /**
     * @return WebhookMock
     */
    public function getWebhook(): WebhookMock
    {
        return $this->webhook ??= new WebhookMock();
    }

    /**
     * @param array $webhook
     */
    public function setWebhook($webhook)
    {
        $this->webhook = new WebhookMock($webhook);
    }
}
