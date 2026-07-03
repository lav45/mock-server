<?php declare(strict_types=1);

namespace Lav45\MockServer\Bootstrap\Mock;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Migrate
{
    private bool $deprecated = false;

    public function __construct(
        private readonly \Closure        $migrate,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function __invoke(callable|null $next): callable
    {
        return function (array $data) use ($next): array {
            $migrated = ($this->migrate)($data);

            if ($this->deprecated === false && $migrated !== $data) {
                $this->deprecated = true;
                $this->logger->warning('Deprecated mock format detected and migrated on the fly. Please run `bin/migrate` to update your mock files.');
            }

            return $next === null ? $migrated : $next($migrated);
        };
    }
}
