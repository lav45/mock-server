<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Request;

use Lav45\MockServer\Domain\ValueObject\HttpMethod;

final readonly class HttpMethods
{
    private array $items;

    public function __construct(HttpMethod ...$items)
    {
        $this->items = $items;
    }

    public function toArray(): array
    {
        $result = [];
        foreach ($this->items as $item) {
            $result[] = $item->value;
        }
        return $result;
    }

    public static function fromArray(array $items): self
    {
        $result = [];
        foreach ($items as $item) {
            $result[] = new HttpMethod($item);
        }
        return new self(...$result);
    }
}
