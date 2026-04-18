<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\ValueObject;

final readonly class HttpHeaders
{
    private array $items;

    public function __construct(HttpHeader ...$items)
    {
        $this->items = $items;
    }

    public function toArray(): array
    {
        $result = [];
        foreach ($this->items as $item) {
            $result[$item->name] = $item->value;
        }
        return $result;
    }

    /**
     * @param array<string,string|int> $headers
     */
    public static function fromArray(array $headers): self
    {
        $result = [];
        foreach ($headers as $name => $value) {
            $result[] = new HttpHeader($name, (string)$value);
        }
        return new self(...$result);
    }
}
