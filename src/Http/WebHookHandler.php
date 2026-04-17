<?php declare(strict_types=1);

namespace Lav45\MockServer\Http;

use Lav45\MockServer\Domain\Mock\WebHooks\WebHook;

interface WebHookHandler
{
    public function send(WebHook ...$webHooks): void;
}
