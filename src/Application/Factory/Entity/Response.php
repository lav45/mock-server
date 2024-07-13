<?php declare(strict_types=1);

namespace Lav45\MockServer\Application\Factory\Entity;

use Lav45\MockServer\Application\Data\Mock\v1\Response as ResponseData;
use Lav45\MockServer\Application\Factory\Entity\Response\Collection as CollectionEntityFactory;
use Lav45\MockServer\Application\Factory\Entity\Response\Content as ContentEntityFactory;
use Lav45\MockServer\Application\Factory\Entity\Response\Proxy as ProxyEntityFactory;
use Lav45\MockServer\Domain\Entity\Response as ResponseEntity;
use Lav45\MockServer\Domain\Service\Parser;

final readonly class Response
{
    public function __construct(private ResponseData $response) {}

    public function create(Parser $parser): ResponseEntity
    {
        if ($this->response->proxy) {
            return (new ProxyEntityFactory($this->response->proxy, $this->response->delay, $parser))->create();
        }
        if ($this->response->data) {
            return (new CollectionEntityFactory($this->response->data, $this->response->delay, $parser))->create();
        }
        return (new ContentEntityFactory($this->response->content, $this->response->delay, $parser))->create();
    }
}
