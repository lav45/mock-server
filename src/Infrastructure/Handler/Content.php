<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Handler;

use Lav45\MockServer\Application\Query\Request\Response;
use Lav45\MockServer\Application\Query\Request\ResponseHandler;
use Lav45\MockServer\Domain\Model\Response\Content as ContentEntity;

final readonly class Content implements ResponseHandler
{
    public function __construct(private ContentEntity $data) {}

    public function execute(): Response
    {
        DelayHelper::delay($this->data->start->value, $this->data->delay->value);

        return new Response(
            status: $this->data->status->value,
            headers: $this->data->headers->toArray(),
            body: $this->data->body->toString(),
        );
    }
}
