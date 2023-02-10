<?php

namespace lav45\MockServer;

use Symfony\Component\Finder\Finder;

/**
 * Class Scanner
 * @package lav45\MockServer
 */
class Scanner
{
    private Finder $finder;

    /**
     * MockFinder constructor.
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->finder = $this->createFinder($path);
    }

    /**
     * @return \Generator
     * @throws \JsonException
     */
    public function get()
    {
        foreach ($this->getFinder() as $file) {
            $content = file_get_contents($file->getPathname());
            yield from json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        }
    }

    /**
     * @param string $path
     * @return Finder
     */
    protected function createFinder($path)
    {
        return (new Finder())
            ->in($path)
            ->files()
            ->ignoreDotFiles(true)
            ->followLinks()
            ->name("*.json");
    }

    /**
     * @return Finder
     */
    public function getFinder(): Finder
    {
        return $this->finder;
    }
}
