<?php declare(strict_types=1);

namespace lav45\MockServer\Infrastructure\Handler;

use lav45\MockServer\Application\DTO\Request;
use lav45\MockServer\Application\DTO\Response;
use lav45\MockServer\Application\Handler\Response as ResponseHandler;
use lav45\MockServer\Domain\Entity\Response\Content as ContentEntity;

final readonly class Content implements ResponseHandler
{
    use DelayTrait;

    public function __construct(private ContentEntity $data)
    {
    }

    public function handle(Request $request): Response
    {
        $this->delay($request->start, $this->data->delay->value);

        return new Response(
            status: $this->data->status->value,
            headers: $this->data->headers->all(),
            body: $this->data->body->toString()
        );
    }
}