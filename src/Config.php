<?php declare(strict_types=1);

namespace Lav45\MockServer;

use Monolog\Level;

final class Config
{
    private int $port = 8080;

    private string $mocksPath = '/app/mocks';

    private string $locale = 'en_US';

    private Level $logLevel = Level::Info;

    private float $fileWatchTimeout = 0.2;

    public function port(string|int|false $port): self
    {
        if ($port || $port === 0) {
            if ($this->isValidPort($port)) {
                $this->port = (int)$port;
            } else {
                throw new \InvalidArgumentException('Invalid mock port');
            }
        }
        return $this;
    }

    private function isValidPort(string|int $port): bool
    {
        $options = [
            'options' => [
                'min_range' => 0,
                'max_range' => 65535,
            ],
        ];
        return \filter_var($port, FILTER_VALIDATE_INT, $options) !== false;
    }

    public function mocks(string|false $path): self
    {
        if ($path) {
            if (\is_dir($path) && \is_readable($path)) {
                $this->mocksPath = $path;
            } else {
                throw new \InvalidArgumentException('Invalid mocks path');
            }
        }
        return $this;
    }

    public function locale(string|false $locale): self
    {
        if ($locale) {
            $canonicalLocale = \Locale::canonicalize($locale);
            if ($canonicalLocale && \preg_match('/^[a-z]{2}_[A-Z]{2}$/', $canonicalLocale) === 1) {
                $this->locale = $canonicalLocale;
            } else {
                throw new \InvalidArgumentException('Invalid locale');
            }
        }
        return $this;
    }

    public function log(string|false $level): self
    {
        if ($level) {
            try {
                $this->logLevel = Level::fromName($level);
            } catch (\Throwable) {
                throw new \InvalidArgumentException('Invalid log level');
            }
        }
        return $this;
    }

    public function fileWatch(string|float|false $timeout): self
    {
        if ($timeout !== false && $timeout !== '') {
            $validatedTimeout = \filter_var($timeout, FILTER_VALIDATE_FLOAT);
            if ($validatedTimeout === false) {
                throw new \InvalidArgumentException('Invalid timeout');
            }
            if ($validatedTimeout >= 0) {
                $this->fileWatchTimeout = (float)$timeout;
            } else {
                throw new \InvalidArgumentException('Invalid timeout');
            }
        }
        return $this;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getMocksPath(): string
    {
        return $this->mocksPath;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getLogLevel(): Level
    {
        return $this->logLevel;
    }

    public function getFileWatchTimeout(): float
    {
        return $this->fileWatchTimeout;
    }
}
