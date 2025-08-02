<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Handler;

use Lav45\MockServer\Application\Query\Request\ResponseFabric as ResponseFabricInterface;
use Lav45\MockServer\Application\Query\Request\ResponseHandler;
use Lav45\MockServer\Domain\Model\Response as ResponseModel;
use Lav45\MockServer\Domain\Model\Response\Content as ContentEntity;
use Lav45\MockServer\Domain\Model\Response\Proxy as ProxyEntity;
use Lav45\MockServer\Infrastructure\HttpClient\HttpClientInterface;

final readonly class ResponseFabric implements ResponseFabricInterface
{
    public function __construct(private HttpClientInterface $httpClient) {}

    public function create(ResponseModel $data): ResponseHandler
    {
        if ($data instanceof ProxyEntity) {
            return new Proxy($data, $this->httpClient);
        }
        if ($data instanceof ContentEntity) {
            return new Content($data);
        }
        throw new \InvalidArgumentException('Invalid data type');
    }
}
