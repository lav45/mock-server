<?php declare(strict_types=1);

namespace Lav45\MockServer;

use Faker\Generator as Faker;
use Lav45\MockServer\Application\Query\Request\ResponseFabric;
use Lav45\MockServer\Application\Query\Request\WebHook;
use Lav45\MockServer\Infrastructure\Handler\ResponseFabric as ResponseFabricHandler;
use Lav45\MockServer\Infrastructure\Handler\WebHook as WebHookHandler;
use Lav45\MockServer\Infrastructure\HttpClient\HttpClient;
use Lav45\MockServer\Infrastructure\Parser\DataParser;
use Lav45\MockServer\Infrastructure\Parser\ParserFactory;
use Lav45\MockServer\Infrastructure\Repository\Repository;
use Lav45\MockServer\Presenter\Handler\Request;
use Psr\Log\LoggerInterface;

final readonly class RequestFactory implements RequestFactoryInterface
{
    private WebHook $webHookHandler;

    private ResponseFabric $responseFabric;

    private DataParser $parser;

    public function __construct(
        Faker           $faker,
        HttpClient      $httpClient,
        LoggerInterface $logger,
    ) {
        $this->webHookHandler = new WebHookHandler($logger, $httpClient);
        $this->responseFabric = new ResponseFabricHandler($httpClient);
        $this->parser = ParserFactory::create($faker);
    }

    public function create(array $mock): Request
    {
        return new Request(
            repository: new Repository($this->parser, $mock),
            webHookHandler: $this->webHookHandler,
            responseFabric: $this->responseFabric,
        );
    }
}
