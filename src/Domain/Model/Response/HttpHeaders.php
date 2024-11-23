<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Model\Response;

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
}
