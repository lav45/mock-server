<?php declare(strict_types=1);

namespace Lav45\MockServer\Application\Data\Mock\v1;

/**
 * @codeCoverageIgnore
 */
final readonly class Request
{
    public function __construct(
        public string       $url = '/',
        public array|string $method = ['GET'],
    ) {}
}
