<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain;

use Lav45\MockServer\Domain\WebHooks\WebHook;

final readonly class WebHooks
{
    /** @var WebHook[] */
    public array $items;

    public function __construct(WebHook ...$items)
    {
        $this->items = $items;
    }
}
