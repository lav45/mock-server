<?php declare(strict_types=1);

namespace Lav45\MockServer\Bootstrap\Migrator;

final readonly class Pipeline
{
    private function __construct(
        private \Closure $handler,
    ) {}

    public static function create(callable ...$stack): self
    {
        $next = null;
        foreach (\array_reverse($stack) as $fn) {
            $next = $fn($next);
        }
        return new self($next(...));
    }

    public function __invoke(): mixed
    {
        return ($this->handler)(...\func_get_args());
    }
}
