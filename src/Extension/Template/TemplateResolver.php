<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Template;

use Lav45\MockServer\Parser\VariableParser;

final readonly class TemplateResolver
{
    private const string PREFIX = '$template.';

    public function __construct(
        private array $templates,
    ) {}

    public function resolve(array $data, VariableParser $parser): array
    {
        $siblings = [];
        $bodies = [];
        foreach ($data as $key => $value) {
            if (\is_array($value)) {
                $value = $this->resolve($value, $parser);
            }
            if (\is_string($key) && \str_starts_with($key, self::PREFIX)) {
                $bodies[] = $this->expand($key, $value, $parser);
            } else {
                $siblings[$key] = $value;
            }
        }

        if ($bodies === []) {
            return $siblings;
        }

        $result = \array_replace_recursive(...$bodies);
        return \array_replace_recursive($result, $siblings);
    }

    private function expand(string $key, mixed $params, VariableParser $parser): array
    {
        $name = \substr($key, \strlen(self::PREFIX));
        $body = $this->templates[$name] ?? throw new \RuntimeException("Template not found: {$name}");

        return $parser->withData(['template' => $params])->replace($body);
    }
}
