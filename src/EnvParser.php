<?php declare(strict_types=1);

namespace lav45\MockServer;

use Closure;
use Generator;
use lav45\MockServer\Component\ArrayHelper;

final class EnvParser
{
    private array $data = [];

    public function __construct(private readonly FakerParser $faker)
    {
    }

    public function addData(array $data): void
    {
        $this->data = array_merge_recursive($this->data, $data);
    }

    public function replace(mixed $data): mixed
    {
        if (is_string($data)) {
            return $this->replaceAttribute($data);
        }
        if (is_array($data) || $data instanceof Generator) {
            $data = $this->replaceFaker($data);
            return $this->replaceKey($data);
        }
        return $data;
    }

    public function replaceKey(array|Generator $data): array
    {
        return $this->recursiveMap($data, function ($value) {
            return $this->replaceAttribute($value);
        });
    }

    public function replaceFaker(array|Generator $data): array
    {
        return $this->recursiveMap($data, function ($value) {
            return $this->faker->parse($value);
        });
    }

    protected function recursiveMap(array|Generator $data, Closure $func): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->recursiveMap($value, $func);
            } elseif (is_string($value)) {
                $result[$key] = $func($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    protected function replaceAttribute(string $value): mixed
    {
        $pattern = '\s?[.\w]+\s?';
        preg_match('/({{' . $pattern . '}})/u', $value, $matches);
        if ($matches) {
            return $this->getValue($matches);
        }
        return preg_replace_callback('/({' . $pattern . '})/u', [$this, 'getValue'], $value);
    }

    private function getValue(array $matches): mixed
    {
        $key = trim($matches[1], '{ }');
        return ArrayHelper::getValue($this->data, $key);
    }
}