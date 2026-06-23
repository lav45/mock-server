<?php declare(strict_types=1);

namespace Lav45\MockServer\Engine;

use Lav45\MockServer\Domain\WebHook;

interface WebHookHandler
{
    public function send(WebHook $webHook): void;
}
