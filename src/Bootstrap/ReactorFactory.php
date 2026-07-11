<?php declare(strict_types=1);

namespace Lav45\MockServer\Bootstrap;

use FastRoute\Dispatcher;
use Lav45\MockServer\Bootstrap\Mock\Migrate;
use Lav45\MockServer\Bootstrap\Mock\Validate;
use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\HttpClient;
use Lav45\MockServer\Engine\WebHookQueue;
use Lav45\MockServer\Extension\Container;
use Lav45\MockServer\Extension\Extension;
use Lav45\MockServer\Extension\ExtensionProvider;
use Lav45\MockServer\Extension\ExtensionType;
use Lav45\MockServer\Extension\Middleware;
use Lav45\MockServer\Extension\Pipeline;
use Lav45\MockServer\Extension\Prepare\PrepareExtension;
use Lav45\MockServer\Extension\Routing\RoutingExtension;
use Lav45\MockServer\Helper\Pipeline as MockPipeline;
use Lav45\MockServer\Parser\ParserFactory;
use Lav45\MockServer\Parser\VariableParser;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class ReactorFactory
{
    public function __construct(
        private string          $mocksPath,
        private string          $locale,
        /** @var list<string> */
        private array           $filterHeaders,
        private HttpClient      $httpClient,
        private WebHookQueue    $webHookQueue,
        private LoggerInterface $logger = new NullLogger(),
        /** @var list<Extension> */
        private array           $extensions = [],
        private string|null     $schema = null,
        private array           $env = [],
        private string          $migratePath = __DIR__ . '/../../migrates',
    ) {}

    public function create(): RequestHandler
    {
        $parser = new ParserFactory($this->locale)->create()
            ->withData(['env' => $this->env]);

        $dataBuilder = new DataBuilder($this->filterHeaders);

        $mocks = new MockStorage($this->mocksPath, $this->logger)->getData();

        $schemaValidator = new SchemaValidator();

        $stack = [
            new Migrate(Migrator::create($this->migratePath), $this->logger),
        ];
        if ($this->schema !== null) {
            $stack[] = new Validate(
                $schemaValidator->withSchema($this->schema),
            );
        }
        $handler = MockPipeline::create(...$stack);

        $dispatcher = new DispatcherFactory(
            handler: $handler(...),
            logger: $this->logger,
        )->create($mocks);

        $container = new Container([
            DataBuilder::class => $dataBuilder,
            HttpClient::class => $this->httpClient,
            WebHookQueue::class => $this->webHookQueue,
            VariableParser::class => $parser,
            Dispatcher::class => $dispatcher,
            LoggerInterface::class => $this->logger,
        ]);

        $extensions = [];
        foreach ($this->extensions as $extension) {
            $class = $extension->class;
            if (\is_subclass_of($class, ExtensionProvider::class) === false) {
                throw new \InvalidArgumentException("Invalid extension: {$class}");
            }
            if ($extension->schema !== null) {
                try {
                    $schemaValidator
                        ->withSchema($extension->schema)
                        ->validate($extension->config);
                } catch (\Throwable $e) {
                    throw new \InvalidArgumentException("Extension {$class} config is invalid: {$e->getMessage()}", previous: $e);
                }
            }
            $extensions[] = [new $class(), $extension->config];
        }

        $coreExtensions = [
            new RoutingExtension()->create($container, []),
            new PrepareExtension()->create($container, []),
        ];

        $middleware = [
            ...$this->createExtensions($extensions, ExtensionType::System, $container),
            ...$coreExtensions,
            ...$this->createExtensions($extensions, ExtensionType::Application, $container),
        ];

        return new Pipeline(...$middleware);
    }

    /**
     * @param list<array{ExtensionProvider, array}> $extensions
     * @return list<Middleware>
     */
    private function createExtensions(array $extensions, ExtensionType $type, ContainerInterface $container): array
    {
        $middleware = [];
        foreach ($extensions as [$extension, $config]) {
            if ($extension->type() === $type) {
                try {
                    $middleware[] = $extension->create($container, $config);
                } catch (\Throwable $exception) {
                    throw new \InvalidArgumentException($extension::class . " failed to initialize: {$exception->getMessage()}", previous: $exception);
                }
            }
        }
        return $middleware;
    }
}
