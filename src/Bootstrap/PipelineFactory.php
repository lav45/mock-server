<?php declare(strict_types=1);

namespace Lav45\MockServer\Bootstrap;

use Lav45\MockServer\DataFactory\CollectionFactory;
use Lav45\MockServer\DataFactory\Condition\ConditionFactory;
use Lav45\MockServer\DataFactory\Condition\ConditionHandler;
use Lav45\MockServer\DataFactory\Condition\SpecificationFactory;
use Lav45\MockServer\DataFactory\ContentFactory;
use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\DataFactory\DirectFactory;
use Lav45\MockServer\DataFactory\ParserFactory;
use Lav45\MockServer\DataFactory\ProxyFactory;
use Lav45\MockServer\DataFactory\WebHooksFactory;
use Lav45\MockServer\Engine\HttpClient;
use Lav45\MockServer\Engine\WebHookQueue;
use Lav45\MockServer\Middleware\CollectionMiddleware;
use Lav45\MockServer\Middleware\ConditionMiddleware;
use Lav45\MockServer\Middleware\ContentMiddleware;
use Lav45\MockServer\Middleware\DirectMiddleware;
use Lav45\MockServer\Middleware\MiddlewareHandler;
use Lav45\MockServer\Middleware\MiddlewarePipeline;
use Lav45\MockServer\Middleware\PrepareMiddleware;
use Lav45\MockServer\Middleware\ProxyMiddleware;
use Lav45\MockServer\Middleware\ThrottlingMiddleware;
use Lav45\MockServer\Middleware\WebHookMiddleware;
use Lav45\MockServer\Parser\VariableParser;
use Lav45\MockServer\Responder\ContentResponder;
use Lav45\MockServer\Responder\DirectHandler;
use Lav45\MockServer\Responder\ProxyResponder;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class PipelineFactory
{
    public function __construct(
        private VariableParser  $parser,
        private DataBuilder     $dataBuilder,
        private HttpClient      $httpClient,
        private WebHookQueue    $webHookQueue,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function create(): MiddlewareHandler
    {
        return new MiddlewarePipeline(
            new PrepareMiddleware(
                parserFactory: new ParserFactory($this->parser),
            ),
            new ConditionMiddleware(
                factory: new ConditionFactory(new SpecificationFactory()),
                handler: new ConditionHandler(),
            ),
            new DirectMiddleware(
                factory: new DirectFactory($this->dataBuilder),
                handler: new DirectHandler(
                    httpClient: $this->httpClient->withLabel('Direct'),
                    logger: $this->logger,
                ),
            ),
            new WebHookMiddleware(
                factory: new WebHooksFactory($this->dataBuilder),
                queue: $this->webHookQueue,
            ),
            new ThrottlingMiddleware($this->dataBuilder),
            new ContentMiddleware(
                factory: new ContentFactory($this->dataBuilder),
                responder: new ContentResponder(),
            ),
            new ProxyMiddleware(
                factory: new ProxyFactory($this->dataBuilder),
                responder: new ProxyResponder(
                    httpClient: $this->httpClient->withLabel('Proxy'),
                ),
            ),
            new CollectionMiddleware(
                factory: new CollectionFactory($this->dataBuilder),
                responder: new ContentResponder(),
            ),
        );
    }
}
