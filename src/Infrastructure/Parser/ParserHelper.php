<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Parser;

use Lav45\MockServer\Infrastructure\Component\ArrayHelper;

final readonly class ParserHelper
{
    public function __construct(
        private string $pattern,
    ) {}

    public function replace(mixed $data, \Closure $value): mixed
    {
        if (\is_string($data)) {
            return $this->replaceAttribute($data, $value);
        }
        if (\is_array($data)) {
            return $this->replaceMap($data, $value);
        }
        return $data;
    }

    private function replaceMap(array $data, \Closure $value): array
    {
        return ArrayHelper::map($data, fn($item) => $this->replaceAttribute($item, $value));
    }

    private function replaceAttribute(string $item, \Closure $value): mixed
    {
        \preg_match('/({{\s*' . $this->pattern . '\s*}})/iu', $item, $matches);
        if ($matches) {
            return $value($matches);
        }
        return \preg_replace_callback('/({\s*' . $this->pattern . '\s*})/iu', $value, $item);
    }
}
