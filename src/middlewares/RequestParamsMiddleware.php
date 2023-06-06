<?php

namespace lav45\MockServer\middlewares;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use lav45\MockServer\EnvParser;
use lav45\MockServer\RequestHelper;

/**
 * Class RequestParamsMiddleware
 * @package lav45\MockServer\middlewares
 */
class RequestParamsMiddleware implements Middleware
{
    /**
     * @param EnvParser $parser
     */
    public function __construct(private readonly EnvParser $parser)
    {
    }

    public function handleRequest(Request $request, RequestHandler $requestHandler): Response
    {
        $helper = RequestHelper::getInstance($request);
        $this->parser->addData([
            'request' => [
                'get' => $helper->get(),
                'post' => $helper->post(),
                'urlParams' => $helper->getUrlParams()
            ]
        ]);
        return $requestHandler->handleRequest($request);
    }
}