<?php declare(strict_types=1);

namespace lav45\MockServer\middlewares;

use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use lav45\MockServer\EnvParser;
use lav45\MockServer\FakerParser;
use lav45\MockServer\Request\RequestWrapper;
use lav45\MockServer\RequestHandler\WrappedRequestHandlerInterface;

class InitParserMiddleware extends BaseMiddleware
{
    public function __construct(
        private readonly FakerParser $faker,
        private readonly array       $env,
    )
    {
    }

    public function handleWrappedRequest(RequestWrapper $request, RequestHandler $requestHandler): Response
    {
        $parser = new EnvParser($this->faker);
        $parser->addData([
            'env' => $parser->replaceFaker($this->env),
            'request' => [
                'get' => $request->get(),
                'post' => $request->post(),
                'urlParams' => $request->getUrlParams()
            ]
        ]);

        $request->setAttribute(EnvParser::class, $parser);

        if ($requestHandler instanceof WrappedRequestHandlerInterface) {
            return $requestHandler->handleWrappedRequest($request);
        }
        return $requestHandler->handleRequest($request->getRequest());
    }
}