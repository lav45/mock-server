<?php declare(strict_types=1);

namespace Lav45\MockServer\Responder;

use Amp\Http\Server\Response as HttpResponse;
use Lav45\MockServer\Domain\Mock\Response;
use Lav45\MockServer\Domain\Mock\Response\ContentResponse as ContentEntity;

final readonly class ContentResponder implements ResponderInterface
{
    public function execute(Response $data): HttpResponse
    {
        if ($data instanceof ContentEntity === false) {
            throw new \RuntimeException(\sprintf('Response data class %s is not allowed.', \get_class($data)));
        }

        return new HttpResponse(
            status: $data->status->value,
            headers: $data->headers->toArray(),
            body: $data->body->toString(),
        );
    }
}
