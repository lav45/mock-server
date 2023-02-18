<?php

namespace lav45\MockServer;

use lav45\MockServer\components\DTObject;
use lav45\MockServer\Mock\Request;
use lav45\MockServer\Mock\Response;
use lav45\MockServer\Mock\Webhook;

/**
 * Class Mock
 * @package lav45\MockServer
 */
class Mock extends DTObject
{
    /** @var array */
    public array $env = [];
    /** @var Request */
    private Request $request;
    /** @var Response */
    private Response $response;
    /** @var Webhook[] */
    private array $webhooks = [];

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request ??= new Request();
    }

    /**
     * @param array $request
     */
    public function setRequest(array $request)
    {
        $this->request = new Request($request);
    }

    /**
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response ??= new Response();
    }

    /**
     * @param array $response
     */
    public function setResponse(array $response)
    {
        $this->response = new Response($response);
    }

    /**
     * @return Webhook[]
     */
    public function getWebhooks()
    {
        return $this->webhooks;
    }

    /**
     * @param array $webhooks
     */
    public function setWebhooks(array $webhooks)
    {
        $this->webhooks = [];
        foreach ($webhooks as $webhook) {
            $this->webhooks[] = new Webhook($webhook);
        }
    }
}
