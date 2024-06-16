<?php declare(strict_types=1);

namespace lav45\MockServer\Infrastructure\Handler;

use lav45\MockServer\Application\Factory\Handler\Response as ResponseHandlerFactory;
use lav45\MockServer\Application\Handler\Response as ResponseHandler;
use lav45\MockServer\Domain\Entity\Response as ResponseEntity;
use lav45\MockServer\Domain\Entity\Response\Collection as CollectionEntity;
use lav45\MockServer\Domain\Entity\Response\Content as ContentEntity;
use lav45\MockServer\Domain\Entity\Response\Proxy as ProxyEntity;
use lav45\MockServer\Infrastructure\Wrapper\HttpClient;

final readonly class Response implements ResponseHandlerFactory
{
    public function __construct(private HttpClient $httpClient) {}

    public function create(ResponseEntity $data): ResponseHandler
    {
        if ($data instanceof ProxyEntity) {
            return new Proxy($data, $this->httpClient);
        }
        if ($data instanceof CollectionEntity) {
            return new Collection($data);
        }
        if ($data instanceof ContentEntity) {
            return new Content($data);
        }
        throw new \InvalidArgumentException('Invalid data type');
    }
}
