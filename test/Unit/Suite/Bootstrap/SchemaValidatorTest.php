<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Bootstrap;

use Lav45\MockServer\Bootstrap\SchemaValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SchemaValidatorTest extends TestCase
{
    private SchemaValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new SchemaValidator()
            ->withSchema(\dirname(__DIR__, 4) . '/schema/mock.schema.json');
    }

    #[DataProvider('validMockProvider')]
    public function testValidMock(array $mock): void
    {
        $this->expectNotToPerformAssertions();
        $this->validator->validate($mock);
    }

    #[DataProvider('invalidMockProvider')]
    public function testInvalidMock(array $mock): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('does not match schema');
        $this->validator->validate($mock);
    }

    public function testWithSchemaThrowsWhenSchemaFileIsUnreadable(): void
    {
        $schemaFile = '/no/such/mock.schema.json';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Unable to read schema file: "' . $schemaFile . '"');

        new SchemaValidator()->withSchema($schemaFile);
    }

    public static function validMockProvider(): array
    {
        return [
            'empty mock' => [[]],
            'minimal request' => [['request' => ['path' => '/']]],
            'method as array' => [['request' => ['method' => ['POST', 'PUT'], 'path' => '/users']]],
            'version' => [['version' => 2, 'request' => ['path' => '/']]],
            'env mixed values' => [[
                'env' => ['id' => '{{faker.uuid}}', 'amount' => 1000],
                'request' => ['path' => '/'],
            ]],
            'content response' => [[
                'response' => [
                    'type' => 'content',
                    'status' => 200,
                    'headers' => ['content-type' => 'application/json'],
                    'body' => ['status' => 'OK'],
                    'delay' => 0.2,
                ],
            ]],
            'content status as template' => [[
                'response' => ['status' => '{{env.code}}', 'body' => 'text'],
            ]],
            'proxy response' => [[
                'response' => [
                    'type' => 'proxy',
                    'url' => 'https://api.site.com/{request.params.path}',
                    'headers' => ['Authorization' => 'Bearer JWT.token'],
                    'content' => ['account' => ['id' => '{{faker.uuid}}']],
                ],
            ]],
            'data response with items' => [[
                'response' => [
                    'type' => 'data',
                    'items' => [['id' => 1, 'name' => 'Dana']],
                    'pagination' => ['pageParam' => 'page', 'defaultPageSize' => 20],
                    'result' => '{{response.items}}',
                ],
            ]],
            'data response with file' => [[
                'response' => ['type' => 'data', 'file' => '/app/mocks/__data/file.json'],
            ]],
            'direct' => [[
                'direct' => ['url' => 'http://internal.api/x', 'headers' => ['X-Source' => 'mock-server']],
            ]],
            'webhooks' => [[
                'webhooks' => [
                    ['delay' => 1, 'method' => 'POST', 'url' => 'https://api.site.com/hook', 'headers' => ['X-Api' => 'token'], 'body' => ['ping' => true]],
                ],
            ]],
            'conditions comparison' => [[
                'conditions' => [
                    ['match' => ['>', '{{request.body.amount}}', 1000], 'response' => ['status' => 402, 'body' => ['error' => 'limit']]],
                ],
            ]],
            'conditions with empty webhooks' => [[
                'conditions' => [
                    ['match' => ['exists', '{{request.headers.x-dry-run}}'], 'response' => ['body' => 'ok'], 'webhooks' => []],
                ],
            ]],
            'match nested logical' => [[
                'conditions' => [
                    ['match' => ['or',
                        ['and',
                            ['>', '{{request.body.amount}}', 1000],
                            ['in', '{{request.body.currency}}', ['USD', 'EUR']],
                        ],
                        ['not', ['=', '{{request.headers.x-role}}', 'premium']],
                    ], 'response' => ['body' => 'x']],
                ],
            ]],
            'match operators' => [[
                'conditions' => [
                    ['match' => ['~', '{{request.params.id}}', '^test-'], 'response' => ['body' => 'a']],
                    ['match' => ['contains', '{{request.body.tag}}', 'foo'], 'response' => ['body' => 'b']],
                    ['match' => ['empty', '{{request.body.note}}'], 'response' => ['body' => 'c']],
                    ['match' => [], 'response' => ['body' => 'd']],
                ],
            ]],
            'empty maps' => [[
                'env' => [],
                'response' => ['headers' => [], 'body' => ''],
            ]],
        ];
    }

    public static function invalidMockProvider(): array
    {
        return [
            'unknown top-level key' => [['foo' => 'bar']],
            'path without leading slash' => [['request' => ['path' => 'users']]],
            'unknown request key' => [['request' => ['path' => '/', 'query' => 'x']]],
            'unknown response field' => [['response' => ['id' => 1]]],
            'unknown response type' => [['response' => ['type' => 'xxx']]],
            'proxy without url' => [['response' => ['type' => 'proxy']]],
            'proxy with status' => [['response' => ['type' => 'proxy', 'url' => 'http://x', 'status' => 200]]],
            'webhook without url' => [['webhooks' => [['method' => 'POST']]]],
            'webhook unknown field' => [['webhooks' => [['url' => 'http://x', 'retries' => 3]]]],
            'direct without url' => [['direct' => ['headers' => ['X-A' => 'b']]]],
            'condition without response' => [['conditions' => [['match' => ['exists', '{{x}}']]]]],
            'condition without match' => [['conditions' => [['response' => ['body' => 'x']]]]],
            'match field not template' => [['conditions' => [['match' => ['=', 'amount', 5], 'response' => ['body' => 'x']]]]],
            'match unknown operator' => [['conditions' => [['match' => ['foo', '{{x}}', 1], 'response' => ['body' => 'x']]]]],
            'match not with extra items' => [['conditions' => [['match' => ['not', ['exists', '{{x}}'], ['exists', '{{y}}']], 'response' => ['body' => 'x']]]]],
            'status as boolean' => [['response' => ['status' => true]]],
            'method as number' => [['request' => ['method' => 5, 'path' => '/']]],
        ];
    }
}
