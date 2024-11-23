<?php declare(strict_types=1);

namespace Lav45\MockServer\Application\Query\Request;

use Lav45\MockServer\Domain\Model\WebHook as WebHookModel;

interface WebHook
{
    public function send(WebHookModel ...$webHooks): void;
}
