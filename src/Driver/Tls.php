<?php declare(strict_types=1);

namespace Lav45\MockServer\Driver;

final readonly class Tls
{
    public function __construct(
        public int         $port,
        public string      $cert,
        public string      $key,
        public string|null $passphrase = null,
    ) {}
}
