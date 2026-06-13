<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain;

final readonly class Conditions
{
    /** @var Condition[] */
    public array $items;

    public function __construct(Condition ...$items)
    {
        $this->items = $items;
    }

    public static function fromArray(array $data): self
    {
        $items = [];
        foreach ($data as $condition) {
            $items[] = Condition::fromArray($condition);
        }
        return new self(...$items);
    }
}
