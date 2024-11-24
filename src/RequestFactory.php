<?php declare(strict_types=1);

namespace Lav45\MockServer;

use Faker\Generator as Faker;
use Lav45\MockServer\Application\Query\Request\ResponseFabric;
use Lav45\MockServer\Application\Query\Request\WebHook;
use Lav45\MockServer\Infrastructure\Component\ArrayHelper;
use Lav45\MockServer\Infrastructure\Handler\ResponseFabric as ResponseFabricHandler;
use Lav45\MockServer\Infrastructure\Handler\WebHook as WebHookHandler;
use Lav45\MockServer\Infrastructure\Parser\ParserFactory;
use Lav45\MockServer\Infrastructure\Repository\Repository;
use Lav45\MockServer\Infrastructure\Service\HttpClient;
use Lav45\MockServer\Presenter\Handler\Request;
use Psr\Log\LoggerInterface;

final readonly class RequestFactory implements RequestFactoryInterface
{
    private WebHook $webHookHandler;

    private ResponseFabric $responseFabric;

    public function __construct(
        private Faker           $faker,
        private HttpClient      $httpClient,
        private LoggerInterface $logger,
    ) {
        $this->webHookHandler = new WebHookHandler($this->logger, $this->httpClient);
        $this->responseFabric = new ResponseFabricHandler($this->httpClient);
    }

    public function create(array $mock): Request
    {
        $env = ArrayHelper::getValue($mock, 'env', []);
        $parserFactory = new ParserFactory($this->faker, $env);
        $repository = new Repository($parserFactory, $mock);

        return new Request(
            repository: $repository,
            webHookHandler: $this->webHookHandler,
            responseFabric: $this->responseFabric,
        );
    }
}
