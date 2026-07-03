<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Bootstrap;

use Lav45\MockServer\Bootstrap\SchemaValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Guards that every shipped functional mock conforms to the schema, so the schema never
 * rejects a legitimate mock that the server is expected to serve.
 */
final class MocksConformToSchemaTest extends TestCase
{
    #[DataProvider('mockProvider')]
    public function testFunctionalMockConformsToSchema(array $mock): void
    {
        $this->expectNotToPerformAssertions();
        new SchemaValidator()
            ->withSchema(\dirname(__DIR__, 4) . '/schema/mock.schema.json')
            ->validate($mock);
    }

    public static function mockProvider(): array
    {
        $root = \dirname(__DIR__, 4);
        $dirs = [$root . '/test/Functional/mocks', $root . '/test/benchmark'];

        $cases = [];
        foreach ($dirs as $dir) {
            if (\is_dir($dir) === false) {
                continue;
            }
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            );
            foreach ($files as $file) {
                $path = $file->getPathname();
                if (\str_ends_with($path, '.json') === false || \str_contains($path, '__data')) {
                    continue;
                }
                $mocks = \json_decode(\file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
                $rel = \str_replace($root . '/', '', $path);
                foreach ($mocks as $index => $mock) {
                    $cases["{$rel} #{$index}"] = [$mock];
                }
            }
        }
        return $cases;
    }
}
