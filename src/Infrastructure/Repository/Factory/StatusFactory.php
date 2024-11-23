<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Factory;

use Lav45\MockServer\Domain\Model\Response\HttpStatus;
use Lav45\MockServer\Infrastructure\Component\ArrayHelper;
use Lav45\MockServer\Infrastructure\Parser\Parser;

final readonly class StatusFactory
{
    public function __construct(
        private Parser $parser,
    ) {}

    public function create(array $data, string $path, int $default = 200): HttpStatus
    {
        $value = ArrayHelper::getValue($data, $path, $default);
        $value = (int)$this->parser->replace($value);
        return new HttpStatus($value);
    }
}
