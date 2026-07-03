<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Extension\Direct;

use Lav45\MockServer\Extension\Direct\DirectDataInjector;
use Lav45\MockServer\Test\Unit\Components\FakeLogger;
use PHPUnit\Framework\TestCase;

final class DirectDataInjectorTest extends TestCase
{
    public function testEmptyDirectDataLeavesDataUnchanged(): void
    {
        $data = ['response' => ['status' => 200], 'webhooks' => [['url' => 'https://a']]];

        $this->assertSame($data, new DirectDataInjector([])->replace($data));
    }

    public function testReplacesResponse(): void
    {
        $injector = new DirectDataInjector(['response' => ['status' => 201]]);

        $result = $injector->replace(['response' => ['status' => 200]]);

        $this->assertSame(['status' => 201], $result['response']);
    }

    public function testSetsWebhooksWhenNoneExist(): void
    {
        $injector = new DirectDataInjector(['webhooks' => [['url' => 'https://new']]]);

        $result = $injector->replace([]);

        $this->assertSame([['url' => 'https://new']], $result['webhooks']);
    }

    public function testMergesWebhooksWithExisting(): void
    {
        $injector = new DirectDataInjector(['webhooks' => [['url' => 'https://b']]]);

        $result = $injector->replace(['webhooks' => [['url' => 'https://a']]]);

        $this->assertSame([['url' => 'https://a'], ['url' => 'https://b']], $result['webhooks']);
    }

    public function testWarnsWhenOverridingExistingResponse(): void
    {
        $logger = new FakeLogger();
        $injector = new DirectDataInjector(['response' => ['status' => 201]], $logger);

        $injector->replace(['response' => ['status' => 200], 'request' => ['path' => '/direct/1']]);

        $warnings = $logger->getMessages('warning');
        $this->assertCount(1, $warnings);
        $this->assertSame("Rewrite 'response' options for: /direct/1", $warnings[0]);
    }

    public function testDoesNotWarnWhenNoExistingResponse(): void
    {
        $logger = new FakeLogger();
        $injector = new DirectDataInjector(['response' => ['status' => 201]], $logger);

        $injector->replace([]);

        $this->assertCount(0, $logger->getMessages('warning'));
    }
}
