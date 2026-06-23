<?php declare(strict_types=1);

namespace Lav45\MockServer\Engine;

use Lav45\MockServer\Domain\WebHooks;

interface WebHookQueue
{
    public function push(WebHooks $webHooks): void;
}
