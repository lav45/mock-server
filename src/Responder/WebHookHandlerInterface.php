<?php declare(strict_types=1);

namespace Lav45\MockServer\Responder;

use Lav45\MockServer\Domain\Mock\WebHook as WebHookModel;

interface WebHookHandlerInterface
{
    /**
     * @param list<WebHookModel> $webHooks
     */
    public function send(iterable $webHooks): void;
}
