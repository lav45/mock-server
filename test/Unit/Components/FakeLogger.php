<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Components;

use Psr\Log\LoggerInterface;

final class FakeLogger implements LoggerInterface
{
    private array $messages = [];

    public function debug($message, array $context = []): void
    {
        $this->log('debug', $message);
    }

    public function error($message, array $context = []): void
    {
        $this->log('error', $message);
    }

    public function info($message, array $context = []): void
    {
        $this->log('info', $message);
    }

    public function warning($message, array $context = []): void
    {
        $this->log('warning', $message);
    }

    public function notice($message, array $context = []): void
    {
        $this->log('notice', $message);
    }

    public function critical($message, array $context = []): void
    {
        $this->log('critical', $message);
    }

    public function alert($message, array $context = []): void
    {
        $this->log('alert', $message);
    }

    public function emergency($message, array $context = []): void
    {
        $this->log('emergency', $message);
    }

    public function log($level, $message, array $context = []): void
    {
        $this->messages[$level] ??= [];
        $this->messages[$level][] = $message;
    }

    public function getMessages($level): array
    {
        return $this->messages[$level] ?? [];
    }

    public function reset(): void
    {
        $this->messages = [];
    }
}
