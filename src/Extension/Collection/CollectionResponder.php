<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Collection;

use Lav45\MockServer\Domain\Response\ContentResponse;
use Lav45\MockServer\Engine\Http\ServerResponse;

final readonly class CollectionResponder
{
    public function execute(ContentResponse $data): ServerResponse
    {
        return new ServerResponse(
            status: $data->status->value,
            headers: $data->headers->toArray(),
            body: $data->body,
        );
    }
}
