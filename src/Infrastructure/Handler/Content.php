<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Handler;

use Lav45\MockServer\Application\Data\Request;
use Lav45\MockServer\Application\Data\Response;
use Lav45\MockServer\Application\Handler\Response as ResponseHandler;
use Lav45\MockServer\Domain\Entity\Response\Content as ContentEntity;

final readonly class Content implements ResponseHandler
{
    use DelayTrait;

    public function __construct(private ContentEntity $data) {}

    public function handle(Request $request): Response
    {
        $this->delay($request->start, $this->data->delay->value);

        return new Response(
            status: $this->data->status->value,
            headers: $this->data->headers->all(),
            body: $this->data->body->toString(),
        );
    }
}
