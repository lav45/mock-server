<?php declare(strict_types=1);

namespace Lav45\MockServer\Responder;

use Lav45\MockServer\Domain\Mock\Response;
use Lav45\MockServer\Domain\Mock\Response\Content as ContentEntity;
use Lav45\MockServer\Http\ResponseData;

final readonly class ContentResponder implements ResponderInterface
{
    public function execute(Response $data): ResponseData
    {
        if ($data instanceof ContentEntity === false) {
            throw new \RuntimeException(\sprintf('Response data class %s is not allowed.', \get_class($data)));
        }

        return new ResponseData(
            status: $data->status->value,
            headers: $data->headers->toArray(),
            body: $data->body->toString(),
        );
    }
}
