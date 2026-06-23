<?php declare(strict_types=1);

namespace Lav45\MockServer\Bootstrap;

use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\HttpClient;
use Lav45\MockServer\Engine\WebHookQueue;
use Lav45\MockServer\Parser\ParserFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class ReactorFactory
{
    public function __construct(
        private string          $mocksPath,
        private string          $locale,
        private array           $filterHeaders,
        private HttpClient      $httpClient,
        private WebHookQueue    $webHookQueue,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function create(): RequestHandler
    {
        $parser = new ParserFactory($this->locale)->create();
        $dataBuilder = new DataBuilder($this->filterHeaders);

        $pipeline = new PipelineFactory(
            parser: $parser,
            dataBuilder: $dataBuilder,
            httpClient: $this->httpClient,
            webHookQueue: $this->webHookQueue,
            logger: $this->logger,
        )->create();

        $mocks = new MockStorage($this->mocksPath, $this->logger)->getData();
        $dispatcher = new DispatcherFactory(
            migrate: Migrator::create(__DIR__ . '/../../migrates'),
            validator: new MockSchemaValidator(),
            logger: $this->logger,
        )->create($mocks);

        return new RouterRequestHandler(
            errorHandler: new ErrorHandler(),
            dispatcher: $dispatcher,
            handler: $pipeline,
        );
    }
}
