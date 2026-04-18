<?php declare(strict_types=1);

namespace Lav45\MockServer\Responder;

use Amp\Http\Server\Response as HttpResponse;
use Lav45\MockServer\Domain\Response\ContentResponse;

final readonly class ContentResponder
{
    public function execute(ContentResponse $data): HttpResponse
    {
        return new HttpResponse(
            status: $data->status->value,
            headers: $data->headers->toArray(),
            body: $data->body->toString(),
        );
    }
}
