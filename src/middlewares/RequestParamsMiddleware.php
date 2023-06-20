<?php

namespace lav45\MockServer\middlewares;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
use Amp\Http\Server\ClientException;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use lav45\MockServer\EnvParser;
use lav45\MockServer\Request\RequestWrapper;
use lav45\MockServer\RequestHandler\WrappedRequestHandlerInterface;

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
     * @param RequestWrapper $request
     * @param RequestHandler $requestHandler
     * @return Response
     * @throws BufferException
     * @throws StreamException
     * @throws ClientException
     */
    public function handleWrappedRequest(RequestWrapper $request, RequestHandler $requestHandler): Response
    {
        $this->parser->addData([
            'request' => [
                'get' => $request->get(),
                'post' => $request->post(),
                'urlParams' => $request->getUrlParams()
            ]
        ]);
        if ($requestHandler instanceof WrappedRequestHandlerInterface) {
            return $requestHandler->handleWrappedRequest($request);
        }
        return $requestHandler->handleRequest($request->getRequest());
    }
}