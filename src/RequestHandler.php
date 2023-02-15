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
     * @param MockResponseContent $content
     */
    public function __construct(private readonly MockResponseContent $content)
    {
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \Throwable
     */
    public function handleRequest(Request $request): Response
    {
        $body = RequestHelper::replaceAttributes(
            $request->getAttribute(Router::class),
            $this->content->text
        );

        return new Response(
            $this->content->status,
            $this->content->getHeaders(),
            $body,
        );
    }
}