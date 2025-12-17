<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository;

use Lav45\MockServer\Application\Query\Request\Request;
use Lav45\MockServer\Domain\Model\Mock;
use Lav45\MockServer\Infrastructure\Parser\DataParser;
use Lav45\MockServer\Infrastructure\Repository\Handler\Handler;
use Lav45\MockServer\Infrastructure\Repository\Handler\ResponseCollectionHandler;
use Lav45\MockServer\Infrastructure\Repository\Handler\ResponseContentHandler;
use Lav45\MockServer\Infrastructure\Repository\Handler\ResponseProxyHandler;
use Lav45\MockServer\Infrastructure\Repository\Handler\WebHooksHandler;

final readonly class DataMapper
{
    private array $responseHandlers;

    private WebHooksHandler $webHooksHandler;

    public function __construct()
    {
        $this->responseHandlers = [
            ResponseContentHandler::TYPE => new ResponseContentHandler(),
            ResponseProxyHandler::TYPE => new ResponseProxyHandler(),
            ResponseCollectionHandler::TYPE => new ResponseCollectionHandler(),
        ];
        $this->webHooksHandler = new WebHooksHandler();
    }

    public function toModel(DataParser $parser, array $data, Request $request): Mock
    {
        return new Mock(
            response: $this->getResponseHandler($data)
                ->handle(
                    parser: $parser,
                    data: $data['response'] ?? [],
                    request: $request,
                ),
            webHooks: $this->webHooksHandler
                ->handle(
                    parser: $parser,
                    data: $data['webhooks'] ?? [],
                ),
        );
    }

    private function getResponseHandler(array $data): Handler
    {
        if (isset($data['response']['type'])) {
            $type = \strtolower($data['response']['type']);
        } else {
            $type = ResponseContentHandler::TYPE;
        }
        return $this->responseHandlers[$type];
    }
}
