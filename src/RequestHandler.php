<?php

namespace lav45\MockServer;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use lav45\MockServer\components\RequestHelper;
use lav45\MockServer\mock\MockResponseContent;

/**
 * Class RequestHandler
 * @package lav45\MockServer
 */
class RequestHandler implements \Amp\Http\Server\RequestHandler
{
    /**
     * @param MockResponseContent $mockResponseContent
     */
    public function __construct(private readonly MockResponseContent $mockResponseContent)
    {
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \JsonException
     * @throws \Throwable
     */
    public function handleRequest(Request $request): Response
    {
        $response = new Response();
        $response->setStatus($this->mockResponseContent->status);
        $response->setHeaders($this->mockResponseContent->getHeaders());

        $body = RequestHelper::replaceAttributes($request, $this->mockResponseContent->text);

        $response->setBody($body);

        return $response;
    }
}