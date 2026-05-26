<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\DataFactory;

use Amp\Http\Server\Request;
use Lav45\MockServer\DataFactory\ParserFactory;
use Lav45\MockServer\Parser\InlineParser;
use Lav45\MockServer\Parser\ParamParser;
use Lav45\MockServer\Parser\VariableParser;
use Lav45\MockServer\Test\Unit\Components\FakeHttpDriverClient;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

final class ParserFactoryTest extends TestCase
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

    private function createRequest(
        string $method = 'GET',
        string $url = 'https://localhost/',
        array  $headers = [],
        string $body = '',
        array  $params = [],
    ): Request {
        $request = new Request(new FakeHttpDriverClient(), $method, Http::new($url), $headers);
        $request->setAttribute('body', $body);
        $request->setAttribute('params', $params);
        return $request;
    }

    public function testCreateExposesRequestMethod(): void
    {
        $request = $this->createRequest('PUT');
        $parser = new ParserFactory($this->createParser())->create($request, []);
        $this->assertSame('PUT', $parser->replace('{{request.method}}'));
    }

    public function testCreateExposesUrlParams(): void
    {
        $request = $this->createRequest(params: ['id' => '42']);
        $parser = new ParserFactory($this->createParser())->create($request, []);
        $this->assertSame('42', $parser->replace('{{request.params.id}}'));
    }

    public function testCreateExposesQueryParams(): void
    {
        $request = $this->createRequest(url: 'https://localhost/?search=foo&page=2');
        $parser = new ParserFactory($this->createParser())->create($request, []);
        $this->assertSame('foo', $parser->replace('{{request.get.search}}'));
        $this->assertSame('2', $parser->replace('{{request.get.page}}'));
    }

    public function testCreateExposesRawBody(): void
    {
        $request = $this->createRequest(body: 'raw content');
        $parser = new ParserFactory($this->createParser())->create($request, []);
        $this->assertSame('raw content', $parser->replace('{{request.body}}'));
    }

    public function testCreateExposesParsedPostData(): void
    {
        $request = $this->createRequest(
            method: 'POST',
            headers: ['content-type' => ['application/json']],
            body: '{"id":1}',
        );
        $parser = new ParserFactory($this->createParser())->create($request, []);
        $this->assertSame(1, $parser->replace('{{request.post.id}}'));
    }

    public function testCreateExposesRequestHeaders(): void
    {
        $request = $this->createRequest(headers: ['authorization' => ['bearer123']]);
        $parser = new ParserFactory($this->createParser())->create($request, []);
        $this->assertSame('bearer123', $parser->replace('{{request.headers.authorization}}'));
    }

    public function testCreateExposesEnvData(): void
    {
        $request = $this->createRequest();
        $parser = new ParserFactory($this->createParser())->create($request, ['api_key' => 'secret']);
        $this->assertSame('secret', $parser->replace('{{env.api_key}}'));
    }

    public function testCreateExposesNestedEnvData(): void
    {
        $request = $this->createRequest();
        $parser = new ParserFactory($this->createParser())->create($request, ['webhook' => ['url' => 'https://hook.example.com']]);
        $this->assertSame('https://hook.example.com', $parser->replace('{{env.webhook.url}}'));
    }
}
