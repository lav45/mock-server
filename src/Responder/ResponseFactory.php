<?php declare(strict_types=1);

namespace Lav45\MockServer\Responder;

use Amp\Http\Server\Response as HttpResponse;
use Lav45\MockServer\Domain\Mock\Response;

final readonly class ResponseFactory implements \Lav45\MockServer\Http\ResponseFactory
{
    public function __construct(
        /** @var non-empty-list<non-empty-string, ResponderInterface> */
        private array $responders = [],
        private array $middlewares = [],
    ) {}

    public function create(Response $data): HttpResponse
    {
        $responder = $this->responders[\get_class($data)]
            ?? throw new \InvalidArgumentException(\sprintf("Not found Responder for data class %s.", \get_class($data)));

        $middlewares = $this->middlewares;
        $middlewares[] = static fn(Response $data): HttpResponse => $responder->execute($data);

        $handler = Pipeline::create(...$middlewares);

        return $handler($data);
    }
}
