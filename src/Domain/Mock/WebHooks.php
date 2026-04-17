<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Mock;

use Lav45\MockServer\Domain\Mock\WebHooks\WebHook;

final readonly class WebHooks
{
    public array $items;

    public function __construct(WebHook ...$items)
    {
        $this->items = $items;
    }
}
