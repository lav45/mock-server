<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository;

use Lav45\MockServer\Application\Query\Request\Request;
use Lav45\MockServer\Domain\Model\Mock;
use Lav45\MockServer\Infrastructure\Parser\Parser;
use Lav45\MockServer\Infrastructure\Repository\Handler\HandlerFactory;
use Lav45\MockServer\Infrastructure\Repository\Handler\WebHooksHandler;

final readonly class DataMapper
{
    public function __construct(
        private Parser $parser,
    ) {}

    public function toModel(array $data, Request $request): Mock
    {
        $webHooks = new WebHooksHandler($this->parser)->handle($data);

        $response = HandlerFactory::fromData($data)
            ->create($this->parser)
            ->handle($data, $request);

        return new Mock(
            response: $response,
            webHooks: $webHooks,
        );
    }
}
