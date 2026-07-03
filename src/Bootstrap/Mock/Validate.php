<?php declare(strict_types=1);

namespace Lav45\MockServer\Bootstrap\Mock;

use Lav45\MockServer\Bootstrap\SchemaValidator;

final readonly class Validate
{
    public function __construct(
        private SchemaValidator $validator,
    ) {}

    public function __invoke(callable|null $next): callable
    {
        return function (array $data) use ($next): array {
            $this->validator->validate($data);

            return $next === null ? $data : $next($data);
        };
    }
}
