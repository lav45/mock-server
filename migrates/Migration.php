<?php declare(strict_types=1);

abstract readonly class Migration
{
    public function __invoke(array $data, callable $next): array
    {
        // class name format: v{version}_{name}
        $version = (int)substr(static::class, 1, strrpos(static::class, '_') - 1);
        if (($data['version'] ?? 0) < $version) {
            $data = ['version' => $version] + $this->execute($data);
        }
        return $next($data);
    }

    abstract protected function execute(array $data): array;
}
