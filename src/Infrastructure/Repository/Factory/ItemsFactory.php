<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Factory;

use Lav45\MockServer\Infrastructure\Component\ArrayHelper;
use Lav45\MockServer\Infrastructure\Parser\Parser;

final readonly class ItemsFactory
{
    public function __construct(
        private Parser $parser,
    ) {}

    public function from(array $data, string $jsonPath, string $filePath): array
    {
        $file = ArrayHelper::getValue($data, $filePath);
        if ($file !== null) {
            $content = \file_get_contents($file);
            \assert(\json_validate($content), 'Invalid file content');
            $items = \json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } else {
            $items = ArrayHelper::getValue($data, $jsonPath, []);
        }
        return $this->parser->replace($items);
    }
}
