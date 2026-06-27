<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Domain\Condition\Specification;

use Lav45\MockServer\Domain\Condition\Specification\MatchesSpecification;
use PHPUnit\Framework\TestCase;

final class MatchesSpecificationTest extends TestCase
{
    public function testMatchesPattern(): void
    {
        $this->assertTrue(new MatchesSpecification('foo\d+')->isSatisfiedBy('foo42'));
    }

    public function testDoesNotMatchPattern(): void
    {
        $this->assertFalse(new MatchesSpecification('foo\d+')->isSatisfiedBy('foobar'));
    }

    public function testPatternContainingDelimiterIsEscaped(): void
    {
        // The delimiter used internally is `~`. Without str_replace the pattern `a~b`
        // would corrupt the regex. With escaping it must match the literal string `a~b`.
        $this->assertTrue(new MatchesSpecification('a~b')->isSatisfiedBy('a~b'));
    }

    public function testReturnsFalseForNonStringActual(): void
    {
        $this->assertFalse(new MatchesSpecification('\d+')->isSatisfiedBy(42));
    }
}
