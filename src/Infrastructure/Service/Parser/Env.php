<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Service\Parser;

use Lav45\MockServer\Domain\Service\Parser;
use Lav45\MockServer\Infrastructure\Component\ArrayHelper;

final readonly class Env implements Parser
{
    public function withData(array $data): self
    {
        return $this;
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
        $pattern = '\s*env\.(\w+)\s*';
        \preg_match('/({{' . $pattern . '}})/u', $value, $matches);
        if ($matches) {
            return $this->getValue($matches);
        }
        return \preg_replace_callback('/({' . $pattern . '})/u', fn($matches) => $this->getValue($matches), $value);
    }

    private function getValue(array $matches): string|null
    {
        return \getenv($matches[2]) ?: null;
    }
}
