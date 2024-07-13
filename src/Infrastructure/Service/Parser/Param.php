<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Service\Parser;

use Lav45\MockServer\Domain\Service\Parser;
use Lav45\MockServer\Infrastructure\Component\ArrayHelper;

final class Param implements Parser
{
    public function __construct(private array $data = []) {}

    public function withData(array $data): self
    {
        $new = clone $this;
        $new->data = \array_merge_recursive($this->data, $data);
        return $new;
    }

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
        $pattern = '\s*[.\w]+\s*';
        \preg_match('/({{' . $pattern . '}})/u', $value, $matches);
        if ($matches) {
            return $this->getValue($matches);
        }
        return \preg_replace_callback('/({' . $pattern . '})/u', fn($matches) => $this->getValue($matches), $value);
    }

    private function getValue(array $matches): mixed
    {
        $key = \trim($matches[1], '{ }');
        return ArrayHelper::getValue($this->data, $key, $matches[1]);
    }
}
