<?php

namespace lav45\MockServer;

/**
 * Class Scanner
 * @package lav45\MockServer
 */
class Scanner
{
    /**
     * Scanner constructor.
     * @param string $path
     */
    public function __construct(private readonly string $path)
    {
    }

    /**
     * @return \Generator
     * @throws \JsonException
     */
    public function get()
    {
        foreach ($this->getFiles($this->path, 'json') as $file) {
            $content = file_get_contents($file);
            yield from json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        }
    }

    /**
     * @return \Generator
     */
    protected function getFiles(string $path, string $extension)
    {
        foreach (glob($path . '/*') as $item) {
            if (is_dir($item)) {
                yield from $this->getFiles($item, $extension);
            } elseif (is_file($item) && pathinfo($item)['extension'] === $extension) {
                yield $item;
            }
        }
    }
}
