<?php declare(strict_types=1);

namespace Lav45\MockServer\Driver;

use Lav45\MockServer\Domain\WebHooks;
use Lav45\MockServer\Engine\WebHookHandler;

use function Amp\async;
use function Amp\delay;

final readonly class WebHookQueue implements \Lav45\MockServer\Engine\WebHookQueue
{
    public function __construct(
        private WebHookHandler $handler,
    ) {}

    public function push(WebHooks $webHooks): void
    {
        async(function () use ($webHooks): void {
            foreach ($webHooks->items as $webHook) {
                if ($webHook->delay->value > 0) {
                    delay($webHook->delay->value);
                }
                $this->handler->send($webHook);
            }
        });
    }
}
