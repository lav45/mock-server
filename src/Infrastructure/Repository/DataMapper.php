<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository;

use Lav45\MockServer\Application\Query\Request\Request;
use Lav45\MockServer\Domain\Model\Mock;
use Lav45\MockServer\Infrastructure\Parser\Parser;
use Lav45\MockServer\Infrastructure\Repository\Handler\ResponseHandlerFactory;
use Lav45\MockServer\Infrastructure\Repository\Handler\WebHooksHandler;
use Psr\Log\LoggerInterface;

final readonly class DataMapper
{
    public function __construct(
        private Parser          $parser,
        private LoggerInterface $logger,
    ) {}

    public function toModel(array $data, Request $request): Mock
    {
        $webHooks = (new WebHooksHandler($this->parser, $this->logger))->handle($data);

        $response = ResponseHandlerFactory::fromData($data)
            ->create($this->parser, $this->logger)
            ->handle($data, $request);

        return new Mock(
            response: $response,
            webHooks: $webHooks,
        );
    }
}
