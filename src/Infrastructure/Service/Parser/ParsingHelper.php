<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Service\Parser;

use Lav45\MockServer\Infrastructure\Component\ArrayHelper;

final readonly class ParsingHelper
{
    public function __construct(
        private string   $pattern,
        /**
         * @example fn(array $matches) => $this->getValue($matches)
         */
        private \Closure $value,
    ) {}

    public function replace(mixed $data): mixed
    {
        if (\is_string($data)) {
            return $this->replaceAttribute($data);
        }
        if (\is_array($data)) {
            return $this->replaceMap($data);
        }
        return $data;
    }

    private function replaceMap(array $data): array
    {
        return ArrayHelper::map($data, fn($value) => $this->replaceAttribute($value));
    }

    private function replaceAttribute(string $value): mixed
    {
        \preg_match('/({{\s*' . $this->pattern . '\s*}})/iu', $value, $matches);
        if ($matches) {
            $fn = $this->value;
            return $fn($matches);
        }
        return \preg_replace_callback('/({\s*' . $this->pattern . '\s*})/iu', $this->value, $value);
    }
}
