<?php declare(strict_types=1);

namespace Lav45\MockServer\Http;

use Lav45\MockServer\Domain\Mock\WebHook;

interface WebHookHandler
{
    /**
     * @param list<WebHook> $webHooks
     */
    public function send(iterable $webHooks): void;
}
