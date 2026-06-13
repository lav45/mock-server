<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Middleware\Condition;

use Lav45\MockServer\DataFactory\Condition\SpecificationFactory;
use Lav45\MockServer\Domain\Condition\Specification\EmptySpecification;
use Lav45\MockServer\Domain\Condition\Specification\ExistsSpecification;
use Lav45\MockServer\Domain\ValueObject\Value;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FieldMatcherTest extends TestCase
{
    private SpecificationFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new SpecificationFactory();
    }

    private function check(mixed $actual, string $op, mixed $expected): bool
    {
        return $this->factory->build($op, $expected)->isSatisfiedBy($actual);
    }

    // --- = ---

    #[DataProvider('eqDataProvider')]
    public function testEq(mixed $actual, mixed $expected, bool $result): void
    {
        $this->assertSame($result, $this->check($actual, '=', $expected));
    }

    public static function eqDataProvider(): array
    {
        return [
            'string match' => ['foo', 'foo', true],
            'string mismatch' => ['foo', 'bar', false],
            'int match' => [42, 42, true],
            'string vs int is strict' => ['42', 42, false],
            'non-numeric int' => ['foo', 42, false],
            'null match' => [null, null, true],
            'bool stays strict' => ['1', true, false],
            'float no truncation' => ['1.9', 1, false],
            'undefined becomes null' => [Value::Undefined, null, true],
        ];
    }

    // --- != ---

    #[DataProvider('neDataProvider')]
    public function testNe(mixed $actual, mixed $expected, bool $result): void
    {
        $this->assertSame($result, $this->check($actual, '!=', $expected));
    }

    public static function neDataProvider(): array
    {
        return [
            'different values' => ['foo', 'bar', true],
            'same values' => ['foo', 'foo', false],
            'string vs int is strict' => ['42', 42, true],
            'non-numeric ne int' => ['foo', 42, true],
        ];
    }

    // --- > / >= / < / <= ---

    #[DataProvider('comparisonDataProvider')]
    public function testComparisons(mixed $actual, string $op, float|int $threshold, bool $result): void
    {
        $this->assertSame($result, $this->check($actual, $op, $threshold));
    }

    public static function comparisonDataProvider(): array
    {
        return [
            'gt true' => [1500, '>', 1000, true],
            'gt false equal' => [1000, '>', 1000, false],
            'gt false less' => [500, '>', 1000, false],
            'gte true equal' => [1000, '>=', 1000, true],
            'gte true greater' => [1001, '>=', 1000, true],
            'gte false' => [999, '>=', 1000, false],
            'lt true' => [500, '<', 1000, true],
            'lt false equal' => [1000, '<', 1000, false],
            'lte true equal' => [1000, '<=', 1000, true],
            'lte true less' => [999, '<=', 1000, true],
            'lte false' => [1001, '<=', 1000, false],
            'string numeric gt' => ['1500', '>', 1000, true],
            'non-numeric gt' => ['abc', '>', 1000, false],
        ];
    }

    // --- contains ---

    #[DataProvider('containsDataProvider')]
    public function testContains(mixed $actual, string $expected, bool $result): void
    {
        $this->assertSame($result, $this->check($actual, 'contains', $expected));
    }

    public static function containsDataProvider(): array
    {
        return [
            'substring present' => ['foobar', 'foo', true],
            'substring absent' => ['foobar', 'baz', false],
            'array includes string' => [['a', 'b', 'c'], 'b', true],
            'array missing string' => [['a', 'b', 'c'], 'd', false],
            'array strict no match' => [[1, 2, 3], '1', false],
            'non-string actual' => [42, 'foo', false],
        ];
    }

    // --- ~ (regex) ---

    #[DataProvider('regexDataProvider')]
    public function testRegex(mixed $actual, string $pattern, bool $result): void
    {
        $this->assertSame($result, $this->check($actual, '~', $pattern));
    }

    public static function regexDataProvider(): array
    {
        return [
            'prefix match' => ['test-123', '^test-', true],
            'prefix no match' => ['prod-123', '^test-', false],
            'numeric pattern' => ['abc123', '\d+', true],
            'non-string actual' => [123, '^\d+$', false],
            'path with slashes' => ['/api/users', '^/api/', true],
        ];
    }

    // --- in ---

    #[DataProvider('inDataProvider')]
    public function testIn(mixed $actual, array $list, bool $result): void
    {
        $this->assertSame($result, $this->check($actual, 'in', $list));
    }

    public static function inDataProvider(): array
    {
        return [
            'string in list' => ['USD', ['USD', 'EUR'], true],
            'string not in list' => ['GBP', ['USD', 'EUR'], false],
            'int in list' => [2, [1, 2, 3], true],
            'strict type miss' => ['2', [1, 2, 3], false],
        ];
    }

    // --- unknown operator ---

    public function testUnknownOperatorReturnsFalse(): void
    {
        $this->assertFalse($this->check('foo', 'unknown_op', 'bar'));
    }

    // --- exists (used directly in ConditionMatcher) ---

    public function testExistsTrueWhenPresent(): void
    {
        $this->assertTrue(new ExistsSpecification()->isSatisfiedBy('value'));
    }

    public function testExistsFalseWhenAbsent(): void
    {
        $this->assertFalse(new ExistsSpecification()->isSatisfiedBy(Value::Undefined));
    }

    // --- empty (used directly in ConditionMatcher) ---

    #[DataProvider('emptyDataProvider')]
    public function testEmpty(mixed $actual, bool $result): void
    {
        $this->assertSame($result, new EmptySpecification()->isSatisfiedBy($actual));
    }

    public static function emptyDataProvider(): array
    {
        return [
            'null is empty' => [null, true],
            'empty string is empty' => ['', true],
            'non-empty is not empty' => ['foo', false],
            'absent field not empty' => [Value::Undefined, false],
        ];
    }
}
