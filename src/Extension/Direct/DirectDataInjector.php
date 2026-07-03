<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Direct;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class DirectDataInjector
{
    public function __construct(
        private array           $directData,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function replace(array $data): array
    {
        if (isset($this->directData['response'])) {
            if (isset($data['response'])) {
                $this->logger->warning("Rewrite 'response' options for: " . ($data['request']['path'] ?? ''));
            }
            $data['response'] = $this->directData['response'];
        }
        if (isset($this->directData['webhooks'])) {
            $data['webhooks'] = isset($data['webhooks'])
                ? \array_merge($data['webhooks'], $this->directData['webhooks'])
                : $this->directData['webhooks'];
        }
        return $data;
    }
}
