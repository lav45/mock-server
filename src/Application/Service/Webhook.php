<?php declare(strict_types=1);

namespace Lav45\MockServer\Application\Service;

use Lav45\MockServer\Domain\ValueObject\Webhook as WebhookItem;

interface Webhook
{
    public function send(WebhookItem ...$webhooks): void;
}
