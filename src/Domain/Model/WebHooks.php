<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Model;

final readonly class WebHooks
{
    /**
     * @var WebHook[]
     */
    public array $items;

    public function __construct(WebHook ...$items)
    {
        $this->items = $items;
    }
}
