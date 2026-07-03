<?php declare(strict_types=1);

namespace Lav45\MockServer\Driver;

use Lav45\MockServer\Extension\Extension;
use Monolog\Level;
use Symfony\Component\Yaml\Yaml;

final class Config
{
    /** @var list<Extension> */
    private array $extensions = [];

    private int $port = 8080;

    private string $mocksPath = '/app/mocks';

    private string $locale = 'en_US';

    private Level $logLevel = Level::Info;

    private array $filterHeaders = [
        'host',
        'content-length',
        'connection',
        'keep-alive',
        'transfer-encoding',
    ];

    private string|null $schema = null;

    public static function fromFile(string|false $path): self
    {
        $config = new self();
        if (empty($path)) {
            return $config;
        }

        if (\is_file($path) === false || \is_readable($path) === false) {
            throw new \InvalidArgumentException('Invalid config path');
        }

        $data = Yaml::parseFile($path) ?? [];
        if (\is_array($data) === false) {
            throw new \InvalidArgumentException('Invalid config file');
        }

        return $config
            ->port($data['port'] ?? false)
            ->mocks($data['mocksPath'] ?? false)
            ->locale($data['locale'] ?? false)
            ->log($data['logLevel'] ?? false)
            ->filterHeaders($data['filterHeaders'] ?? false)
            ->schema($data['schema'] ?? false)
            ->extensions($data['extensions'] ?? []);
    }

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

    public function filterHeaders(string|array|false $headers): self
    {
        if ($headers) {
            if (\is_string($headers)) {
                $headers = \explode(',', $headers);
            }
            $this->filterHeaders = $headers
                |> (static fn(array $parts) => \array_map(static fn(string $s) => \strtolower(\trim($s)), $parts))
                |> \array_filter(...)
                |> \array_values(...);
        }
        return $this;
    }

    public function getLogLevel(): int
    {
        return $this->logLevel->value;
    }

    public function getFilterHeaders(): array
    {
        return $this->filterHeaders;
    }

    public function schema(string|false $path): self
    {
        if ($path) {
            if (\is_file($path) && \is_readable($path)) {
                $this->schema = $path;
            } else {
                throw new \InvalidArgumentException('Invalid schema path');
            }
        }
        return $this;
    }

    public function getSchema(): string|null
    {
        return $this->schema;
    }

    public function extensions(array $extensions): self
    {
        foreach ($extensions as $extension) {
            if (isset($extension['class']) === false) {
                throw new \InvalidArgumentException('Invalid extension: missing class');
            }
            $this->extensions[] = Extension::fromArray($extension);
        }
        return $this;
    }

    /**
     * @return list<Extension>
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }
}
