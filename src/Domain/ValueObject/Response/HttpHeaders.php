<?php declare(strict_types=1);

namespace lav45\MockServer\Domain\ValueObject\Response;

final readonly class HttpHeaders
{
    private array $items;

    public function __construct(HttpHeader ...$items)
    {
        $this->items = $items;
    }

    public function all(): array
    {
        $result = [];
        foreach ($this->items as $item) {
            $result[$item->name] = $item->value;
        }
        return $result;
    }
}
