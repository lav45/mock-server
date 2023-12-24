<?php declare(strict_types=1);

namespace lav45\MockServer\Request\Middleware;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use lav45\MockServer\EnvParser;
use lav45\MockServer\FakerParser;
use lav45\MockServer\Request\Wrapper\RequestWrapper;

final readonly class InitMiddleware implements Middleware
{
    public function __construct(
        private FakerParser $faker,
        private array       $env,
    )
    {
    }

    public function handleRequest(Request $request, RequestHandler $requestHandler): Response
    {
        $requestWrapper = new RequestWrapper($request);
        $request->setAttribute(RequestWrapper::class, $requestWrapper);

        $parser = new EnvParser($this->faker);
        $parser->addData([
            'env' => $parser->replaceFaker($this->env),
            'request' => [
                'get' => $requestWrapper->get(),
                'post' => $requestWrapper->post(),
                'urlParams' => $requestWrapper->getUrlParams()
            ]
        ]);
        $request->setAttribute(EnvParser::class, $parser);

        return $requestHandler->handleRequest($request);
    }
}