<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Service\Parser;

use Lav45\MockServer\Domain\Service\Parser;

final readonly class Env implements Parser
{
    private ParsingHelper $parser;

    public function __construct()
    {
        $this->parser = new ParsingHelper(
            pattern: 'env\.(\w+)',
            value: fn(array $matches) => $this->getValue($matches),
        );
    }

    public function withData(array $data): self
    {
        return $this;
    }

    public function replace(mixed $data): mixed
    {
        return $this->parser->replace($data);
    }

    private function getValue(array $matches): string|null
    {
        return \getenv($matches[2]) ?: null;
    }
}
