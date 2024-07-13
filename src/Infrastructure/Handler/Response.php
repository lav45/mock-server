<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Handler;

use Lav45\MockServer\Application\Factory\Handler\Response as ResponseHandlerFactory;
use Lav45\MockServer\Application\Handler\Response as ResponseHandler;
use Lav45\MockServer\Domain\Entity\Response as ResponseEntity;
use Lav45\MockServer\Domain\Entity\Response\Collection as CollectionEntity;
use Lav45\MockServer\Domain\Entity\Response\Content as ContentEntity;
use Lav45\MockServer\Domain\Entity\Response\Proxy as ProxyEntity;
use Lav45\MockServer\Infrastructure\Wrapper\HttpClient;

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
