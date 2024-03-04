<?php declare(strict_types=1);

namespace lav45\MockServer\Application\Service;

use lav45\MockServer\Domain\ValueObject\Webhook as WebhookItem;

interface Webhook
{
    public function send(WebhookItem ...$webhooks): void;
}