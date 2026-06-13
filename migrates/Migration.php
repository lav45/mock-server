<?php declare(strict_types=1);

abstract readonly class Migration
{
    final public function __invoke(callable|null $next): callable
    {
        return function (array $data) use ($next): array {
            // class name format: v{version}_{name}
            $className = basename(str_replace('\\', '/', static::class));
            $version = (int)substr($className, 1, strpos($className, '_') - 1);
            if (($data['version'] ?? 0) < $version) {
                $data = ['version' => $version] + $this->execute($data);
            }
            return $next === null ? $data : $next($data);
        };
    }

    abstract protected function execute(array $data): array;
}
