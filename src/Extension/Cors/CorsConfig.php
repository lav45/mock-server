<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Cors;

final readonly class CorsConfig
{
    public function __construct(
        /** @var list<string> */
        public array      $origins = ['*'],
        /** @var list<string> */
        public array      $allowMethods = ['*'],
        /** @var list<string> */
        public array      $allowHeaders = ['*'],
        /** @var list<string>|null */
        public array|null $exposeHeaders = ['*'],
        public bool       $allowCredentials = false,
        public int|null   $maxAge = 86400,
    ) {}

    public function allowsAnyOrigin(): bool
    {
        return $this->origins === ['*'];
    }

    public function allowsOrigin(string $origin): bool
    {
        return $this->allowsAnyOrigin() || \in_array($origin, $this->origins, true);
    }
}
