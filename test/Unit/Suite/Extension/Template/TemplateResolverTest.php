<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Extension\Template;

use Lav45\MockServer\Extension\Template\TemplateResolver;
use Lav45\MockServer\Parser\InlineParser;
use Lav45\MockServer\Parser\ParamParser;
use Lav45\MockServer\Parser\VariableParser;
use PHPUnit\Framework\TestCase;

final class TemplateResolverTest extends TestCase
{
    private function createParser(): VariableParser
    {
        return new ParamParser(new class implements InlineParser {
            public function replace(mixed $data): mixed
            {
                return $data;
            }
        });
    }

    public function testReturnsDataUnchangedWhenNoTemplateKeys(): void
    {
        $resolver = new TemplateResolver([]);
        $data = ['response' => ['type' => 'content', 'body' => 'hi']];

        $this->assertSame($data, $resolver->resolve($data, $this->createParser()));
    }

    public function testExpandsTemplateKeyWithBody(): void
    {
        $resolver = new TemplateResolver([
            'greeting' => ['type' => 'content', 'body' => 'hello'],
        ]);
        $data = ['response' => ['$template.greeting' => []]];

        $result = $resolver->resolve($data, $this->createParser());

        $this->assertSame(['response' => ['type' => 'content', 'body' => 'hello']], $result);
    }

    public function testSubstitutesInlineTemplateParam(): void
    {
        $resolver = new TemplateResolver([
            'order' => ['body' => 'id={template.id}'],
        ]);
        $data = ['response' => ['$template.order' => ['id' => '42']]];

        $result = $resolver->resolve($data, $this->createParser());

        $this->assertSame(['response' => ['body' => 'id=42']], $result);
    }

    public function testSubstitutesTypedTemplateParamPreservingType(): void
    {
        $resolver = new TemplateResolver([
            'order' => ['body' => '{{template.payload}}'],
        ]);
        $data = ['response' => ['$template.order' => ['payload' => ['id' => 7, 'ok' => true]]]];

        $result = $resolver->resolve($data, $this->createParser());

        $this->assertSame(['response' => ['body' => ['id' => 7, 'ok' => true]]], $result);
    }

    public function testMergesSiblingsRecursivelyWithMockPriority(): void
    {
        $resolver = new TemplateResolver([
            'hook' => [
                'url' => 'https://template',
                'method' => 'POST',
                'headers' => ['X-A' => '1', 'X-B' => '2'],
            ],
        ]);
        $data = ['webhooks' => [[
            'delay' => 0.1,
            'headers' => ['X-B' => 'override', 'X-C' => '3'],
            '$template.hook' => [],
        ]]];

        $result = $resolver->resolve($data, $this->createParser());

        $expected = ['webhooks' => [[
            'url' => 'https://template',
            'method' => 'POST',
            'headers' => ['X-A' => '1', 'X-B' => 'override', 'X-C' => '3'],
            'delay' => 0.1,
        ]]];
        $this->assertSame($expected, $result);
    }

    public function testExpandsTemplateAtDeepNestingLevel(): void
    {
        $resolver = new TemplateResolver([
            'leaf' => ['value' => 'X'],
        ]);
        $data = ['a' => ['b' => ['c' => ['$template.leaf' => []]]]];

        $result = $resolver->resolve($data, $this->createParser());

        $this->assertSame(['a' => ['b' => ['c' => ['value' => 'X']]]], $result);
    }

    public function testMergesMultipleTemplateKeysInSameObject(): void
    {
        $resolver = new TemplateResolver([
            'one' => ['a' => 1],
            'two' => ['b' => 2],
        ]);
        $data = ['$template.one' => [], '$template.two' => []];

        $result = $resolver->resolve($data, $this->createParser());

        $this->assertSame(['a' => 1, 'b' => 2], $result);
    }

    public function testThrowsWhenTemplateNotFound(): void
    {
        $resolver = new TemplateResolver([]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Template not found: missing');

        $resolver->resolve(['response' => ['$template.missing' => []]], $this->createParser());
    }
}
