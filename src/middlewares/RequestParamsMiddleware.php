<?php

namespace lav45\MockServer\middlewares;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
use Amp\Http\Server\ClientException;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use lav45\MockServer\EnvParser;
use lav45\MockServer\Request\WrappedRequest;
use lav45\MockServer\RequestHandler\WrappedRequestHandlerInterface;
use lav45\MockServer\RequestHelper;

/**
 * Class RequestParamsMiddleware
 * @package lav45\MockServer\middlewares
 */
class RequestParamsMiddleware extends BaseMiddleware
{
    /**
     * @param EnvParser $parser
     */
    public function __construct(private readonly EnvParser $parser)
    {
    }

    /**
     * @param WrappedRequest $request
     * @param RequestHandler $requestHandler
     * @return Response
     * @throws BufferException
     * @throws StreamException
     * @throws ClientException
     */
    public function handleWrappedRequest(WrappedRequest $request, RequestHandler $requestHandler): Response
    {
        $helper = new RequestHelper($request);
        $this->parser->addData([
            'request' => [
                'get' => $helper->get(),
                'post' => $helper->post(),
                'urlParams' => $helper->getUrlParams()
            ]
        ]);
        if ($requestHandler instanceof WrappedRequestHandlerInterface) {
            return $requestHandler->handleWrappedRequest($request);
        }
        return $requestHandler->handleRequest($request->getRequest());
    }
}