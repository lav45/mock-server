<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Middleware\Condition;

use Lav45\MockServer\DataFactory\Condition\SpecificationFactory;
use Lav45\MockServer\Parser\InlineParser;
use Lav45\MockServer\Parser\ParamParser;
use PHPUnit\Framework\TestCase;

final class ConditionMatcherTest extends TestCase
{
    private SpecificationFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new SpecificationFactory();
    }

    /**
     * The expression is resolved up front (as PrepareMiddleware does), then matched on literals.
     */
    private function check(array $expression, InlineParser $parser): bool
    {
        return $this->factory->create($parser->replace($expression))->isSatisfiedBy(null);
    }

    private function parser(array $request = [], array $env = []): InlineParser
    {
        $inner = new class implements InlineParser {
            public function replace(mixed $data): mixed
            {
                return $data;
            }
        };
        $data = [];
        if ($request !== []) {
            $data['request'] = $request;
        }
        if ($env !== []) {
            $data['env'] = $env;
        }
        return new ParamParser($inner)->withData($data);
    }

    // --- Empty match ---

    public function testEmptyMatchAlwaysTrue(): void
    {
        $this->assertTrue($this->check([], $this->parser()));
    }

    // --- field = value ---

    public function testFieldEqMatch(): void
    {
        $this->assertTrue($this->check(
            ['=', '{{request.body.status}}', 'active'],
            $this->parser(['body' => ['status' => 'active']]),
        ));
    }

    public function testFieldEqMismatch(): void
    {
        $this->assertFalse($this->check(
            ['=', '{{request.body.status}}', 'active'],
            $this->parser(['body' => ['status' => 'inactive']]),
        ));
    }

    // --- Numeric comparison ---

    public function testFieldGt(): void
    {
        $this->assertTrue($this->check(
            ['>', '{{request.body.amount}}', 1000],
            $this->parser(['body' => ['amount' => 1500]]),
        ));
        $this->assertFalse($this->check(
            ['>', '{{request.body.amount}}', 1000],
            $this->parser(['body' => ['amount' => 500]]),
        ));
    }

    // --- Dot-notation nested path ---

    public function testDotNotationNestedKey(): void
    {
        $this->assertTrue($this->check(
            ['=', '{{request.body.user.address.city}}', 'Moscow'],
            $this->parser(['body' => ['user' => ['address' => ['city' => 'Moscow']]]]),
        ));
        $this->assertFalse($this->check(
            ['=', '{{request.body.user.address.city}}', 'Moscow'],
            $this->parser(['body' => ['user' => ['address' => ['city' => 'London']]]]),
        ));
    }

    public function testDotNotationMissingPath(): void
    {
        $this->assertFalse($this->check(
            ['=', '{{request.body.user.address.city}}', 'Moscow'],
            $this->parser(['body' => ['user' => []]]),
        ));
    }

    // --- query ---

    public function testQueryFieldMatch(): void
    {
        $this->assertTrue($this->check(
            ['=', '{{request.query.dry_run}}', 'true'],
            $this->parser(['query' => ['dry_run' => 'true']]),
        ));
    }

    // --- headers (with hyphens) ---

    public function testHeaderMatch(): void
    {
        $this->assertTrue($this->check(
            ['=', '{{request.headers.x-role}}', 'admin'],
            $this->parser(['headers' => ['x-role' => 'admin']]),
        ));
    }

    public function testHeaderMismatch(): void
    {
        $this->assertFalse($this->check(
            ['!=', '{{request.headers.x-role}}', 'premium'],
            $this->parser(['headers' => ['x-role' => 'premium']]),
        ));
    }

    // --- params ---

    public function testParamsRegexMatch(): void
    {
        $this->assertTrue($this->check(
            ['~', '{{request.params.id}}', '^test-'],
            $this->parser(['params' => ['id' => 'test-123']]),
        ));
        $this->assertFalse($this->check(
            ['~', '{{request.params.id}}', '^test-'],
            $this->parser(['params' => ['id' => 'prod-456']]),
        ));
    }

    // --- method ---

    public function testMethodMatch(): void
    {
        $this->assertTrue($this->check(
            ['=', '{{request.method}}', 'POST'],
            $this->parser(['method' => 'POST']),
        ));
        $this->assertFalse($this->check(
            ['=', '{{request.method}}', 'POST'],
            $this->parser(['method' => 'GET']),
        ));
    }

    // --- path ---

    public function testPathRegexMatch(): void
    {
        $this->assertTrue($this->check(
            ['~', '{{request.path}}', '^/api/'],
            $this->parser(['path' => '/api/users/42']),
        ));
        $this->assertFalse($this->check(
            ['~', '{{request.path}}', '^/api/'],
            $this->parser(['path' => '/health']),
        ));
    }

    // --- in ---

    public function testInOperator(): void
    {
        $this->assertTrue($this->check(
            ['in', '{{request.body.currency}}', ['USD', 'EUR']],
            $this->parser(['body' => ['currency' => 'USD']]),
        ));
        $this->assertFalse($this->check(
            ['in', '{{request.body.currency}}', ['USD', 'EUR']],
            $this->parser(['body' => ['currency' => 'GBP']]),
        ));
    }

    // --- exists ---

    public function testExistsTrueWhenFieldPresent(): void
    {
        $this->assertTrue($this->check(
            ['exists', '{{request.headers.x-dry-run}}'],
            $this->parser(['headers' => ['x-dry-run' => '1']]),
        ));
    }

    public function testExistsFalseWhenFieldAbsent(): void
    {
        $this->assertFalse($this->check(
            ['exists', '{{request.headers.x-dry-run}}'],
            $this->parser(['headers' => []]),
        ));
    }

    // --- empty ---

    public function testEmptyTrueWhenNull(): void
    {
        $this->assertTrue($this->check(
            ['empty', '{{request.body.note}}'],
            $this->parser(['body' => ['note' => null]]),
        ));
    }

    public function testEmptyTrueWhenEmptyString(): void
    {
        $this->assertTrue($this->check(
            ['empty', '{{request.body.note}}'],
            $this->parser(['body' => ['note' => '']]),
        ));
    }

    public function testEmptyFalseWhenHasValue(): void
    {
        $this->assertFalse($this->check(
            ['empty', '{{request.body.note}}'],
            $this->parser(['body' => ['note' => 'hello']]),
        ));
    }

    public function testEmptyFalseWhenFieldAbsent(): void
    {
        $this->assertFalse($this->check(
            ['empty', '{{request.body.note}}'],
            $this->parser(['body' => []]),
        ));
    }

    // --- not ---

    public function testNotExists(): void
    {
        $this->assertTrue($this->check(
            ['not', ['exists', '{{request.headers.x-dry-run}}']],
            $this->parser(['headers' => []]),
        ));
        $this->assertFalse($this->check(
            ['not', ['exists', '{{request.headers.x-dry-run}}']],
            $this->parser(['headers' => ['x-dry-run' => '1']]),
        ));
    }

    public function testNotIn(): void
    {
        $this->assertTrue($this->check(
            ['not', ['in', '{{request.body.currency}}', ['USD', 'EUR']]],
            $this->parser(['body' => ['currency' => 'GBP']]),
        ));
        $this->assertFalse($this->check(
            ['not', ['in', '{{request.body.currency}}', ['USD', 'EUR']]],
            $this->parser(['body' => ['currency' => 'USD']]),
        ));
    }

    // --- and ---

    public function testAndAllMustMatch(): void
    {
        $expr = ['and',
            ['>', '{{request.body.amount}}', 1000],
            ['in', '{{request.body.currency}}', ['USD', 'EUR']],
            ['!=', '{{request.headers.x-role}}', 'premium'],
        ];

        $this->assertTrue($this->check($expr, $this->parser([
            'body' => ['amount' => 1500, 'currency' => 'USD'],
            'headers' => ['x-role' => 'user'],
        ])));
        $this->assertFalse($this->check($expr, $this->parser([
            'body' => ['amount' => 1500, 'currency' => 'USD'],
            'headers' => ['x-role' => 'premium'],
        ])));
    }

    // --- or ---

    public function testOrAnyCanMatch(): void
    {
        $expr = ['or',
            ['=', '{{request.query.dry_run}}', 'true'],
            ['exists', '{{request.headers.x-dry-run}}'],
        ];

        $this->assertTrue($this->check($expr, $this->parser([
            'query' => ['dry_run' => 'true'],
            'headers' => [],
        ])));
        $this->assertTrue($this->check($expr, $this->parser([
            'query' => [],
            'headers' => ['x-dry-run' => '1'],
        ])));
        $this->assertFalse($this->check($expr, $this->parser([
            'query' => [],
            'headers' => [],
        ])));
    }

    // --- nested and/or ---

    public function testNestedOrOfAndGroups(): void
    {
        // (amount > 1000 AND currency IN [USD,EUR]) OR (x-role != premium AND x-banned = true)
        $expr = ['or',
            ['and',
                ['>', '{{request.body.amount}}', 1000],
                ['in', '{{request.body.currency}}', ['USD', 'EUR']],
            ],
            ['and',
                ['!=', '{{request.headers.x-role}}', 'premium'],
                ['=', '{{request.headers.x-banned}}', 'true'],
            ],
        ];

        $this->assertTrue($this->check($expr, $this->parser([
            'body' => ['amount' => 1500, 'currency' => 'USD'],
        ])));
        $this->assertTrue($this->check($expr, $this->parser([
            'headers' => ['x-role' => 'user', 'x-banned' => 'true'],
        ])));
        $this->assertFalse($this->check($expr, $this->parser([
            'body' => ['amount' => 500, 'currency' => 'USD'],
            'headers' => ['x-role' => 'premium'],
        ])));
    }

    // --- malformed / incomplete expression ---

    public function testIncompleteExpressionNeverMatches(): void
    {
        $this->assertFalse($this->check(
            ['{{request.body.status}}'],
            $this->parser(['body' => ['status' => 'active']]),
        ));
    }

    public function testFieldWithoutValueNeverMatches(): void
    {
        $this->assertFalse($this->check(
            ['=', '{{request.body.status}}'],
            $this->parser(['body' => ['status' => 'active']]),
        ));
    }

    // --- env value in expected ---

    public function testEnvValueInExpected(): void
    {
        $this->assertTrue($this->check(
            ['>', '{{request.body.amount}}', '{{env.limit}}'],
            $this->parser(['body' => ['amount' => 1500]], ['limit' => 1000]),
        ));
        $this->assertFalse($this->check(
            ['>', '{{request.body.amount}}', '{{env.limit}}'],
            $this->parser(['body' => ['amount' => 500]], ['limit' => 1000]),
        ));
    }
}
