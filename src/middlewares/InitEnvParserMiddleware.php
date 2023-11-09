<?php declare(strict_types=1);

namespace lav45\MockServer\middlewares;

use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use lav45\MockServer\EnvParser;
use lav45\MockServer\Request\RequestWrapper;

class InitEnvParserMiddleware extends BaseMiddleware
{
    public function __construct(
        private readonly EnvParser $parser,
        private readonly array     $env,
    )
    {
    }

    public function handleWrappedRequest(RequestWrapper $request, RequestHandler $requestHandler): Response
    {
        $this->parser->reset();
        $this->parser->addData([
            'env' => $this->parser->replaceFaker($this->env)
        ]);

        return $requestHandler->handleRequest($request->getRequest());
    }
}