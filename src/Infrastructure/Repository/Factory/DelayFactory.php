<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Factory;

use Lav45\MockServer\Domain\Model\Response\Delay;
use Lav45\MockServer\Infrastructure\Component\ArrayHelper;
use Lav45\MockServer\Infrastructure\Parser\Parser;

final readonly class DelayFactory
{
    public function __construct(
        private Parser $parser,
    ) {}

    public function create(array $data, string $path): Delay
    {
        $delay = ArrayHelper::getValue($data, $path, 0.0);
        $delay = $this->parser->replace($delay);
        return new Delay((float)$delay);
    }
}
