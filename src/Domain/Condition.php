<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain;

use Lav45\MockServer\Domain\Condition\Specification;
use Lav45\MockServer\Domain\ValueObject\Value;

final readonly class Condition
{
    public function __construct(
        public Specification $match,
        public array         $response,
        public array|Value   $webhooks,
    ) {}
}
