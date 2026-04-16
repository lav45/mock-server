<?php declare(strict_types=1);

namespace Lav45\MockServer\Responder;

use Amp\Http\Server\Response as HttpResponse;
use Lav45\MockServer\Domain\Mock\Response;

final readonly class ResponseFabric implements ResponseFactoryInterface
{
    public function __construct(
        /** @var non-empty-list<non-empty-string, ResponderInterface> */
        public array $responders = [],
    ) {}

    public function create(Response $data): HttpResponse
    {
        $responder = $this->responders[\get_class($data)]
            ?? throw new \InvalidArgumentException(\sprintf("Not found Responder for data class %s.", \get_class($data)));

        return $responder->execute($data);
    }
}
