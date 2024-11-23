<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Factory;

use Lav45\MockServer\Domain\Model\Response\HttpMethod;
use Lav45\MockServer\Infrastructure\Component\ArrayHelper;
use Lav45\MockServer\Infrastructure\Parser\Parser;

final readonly class MethodFactory
{
    public function __construct(
        private Parser $parser,
    ) {}

    public function create(array $data, string $path, string $default): HttpMethod
    {
        $method = ArrayHelper::getValue($data, $path, $default);
        $method = $this->parser->replace($method);
        $method = \strtoupper($method);
        return new HttpMethod($method);
    }
}
