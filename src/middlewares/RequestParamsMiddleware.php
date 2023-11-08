<?php declare(strict_types=1);

namespace lav45\MockServer\middlewares;

use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use lav45\MockServer\EnvParser;
use lav45\MockServer\Request\RequestWrapper;
use lav45\MockServer\RequestHandler\WrappedRequestHandlerInterface;

class RequestParamsMiddleware extends BaseMiddleware
{
    public function __construct(private readonly EnvParser $parser)
    {
    }

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