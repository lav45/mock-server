<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Factory;

use Lav45\MockServer\Domain\Model\Response\Body;
use Lav45\MockServer\Infrastructure\Component\ArrayHelper;
use Lav45\MockServer\Infrastructure\Parser\Parser;

final readonly class BodyFactory
{
    public function __construct(
        private Parser $parser,
    ) {}

    public function from(array $data, string $path): Body
    {
        $value = ArrayHelper::getValue($data, $path);
        $value = $this->parser->replace($value);
        return Body::new($value);
    }

    public function fromContent(array $data, string $textPath, string $jsonPath): Body
    {
        $value = ArrayHelper::getValue($data, $jsonPath);
        $value ??= ArrayHelper::getValue($data, $textPath);
        $value = $this->parser->replace($value);
        return Body::new($value);
    }
}
