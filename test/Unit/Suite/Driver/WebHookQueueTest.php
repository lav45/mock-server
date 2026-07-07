<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Driver;

use Lav45\MockServer\Domain\ValueObject\Body;
use Lav45\MockServer\Domain\ValueObject\Delay;
use Lav45\MockServer\Domain\ValueObject\HttpHeaders;
use Lav45\MockServer\Domain\ValueObject\HttpMethod;
use Lav45\MockServer\Domain\ValueObject\Url;
use Lav45\MockServer\Domain\WebHook;
use Lav45\MockServer\Domain\WebHooks;
use Lav45\MockServer\Driver\WebHookQueue;
use Lav45\MockServer\Extension\WebHook\WebHookHandler;
use Lav45\MockServer\Test\Unit\Components\FakeHttpClient;
use Lav45\MockServer\Test\Unit\Components\FakeLogger;
use PHPUnit\Framework\TestCase;
use Revolt\EventLoop;

final class WebHookQueueTest extends TestCase
{
    private function createWebHook(
        string $url = 'https://hook.example.com',
        float  $delay = 0.0,
    ): WebHook {
        return new WebHook(
            delay: new Delay($delay),
            url: new Url($url),
            method: new HttpMethod('POST'),
            headers: HttpHeaders::fromArray([]),
            body: Body::new(''),
        );
    }

    public function testPushSendsWebhooksInOrder(): void
    {
        $httpClient = new FakeHttpClient();
        $queue = new WebHookQueue(new WebHookHandler($httpClient));

        $queue->push(new WebHooks(
            $this->createWebHook(url: 'https://hook1.example.com'),
            $this->createWebHook(url: 'https://hook2.example.com'),
            $this->createWebHook(url: 'https://hook3.example.com'),
        ));
        EventLoop::run();

        $this->assertSame([
            'https://hook1.example.com',
            'https://hook2.example.com',
            'https://hook3.example.com',
        ], $httpClient->uris());
    }

    public function testPushDoesNothingWhenWebHooksEmpty(): void
    {
        $httpClient = new FakeHttpClient();
        $queue = new WebHookQueue(new WebHookHandler($httpClient));

        $queue->push(new WebHooks());
        EventLoop::run();

        $this->assertCount(0, $httpClient->calls);
    }

    public function testPushSendsAfterDelay(): void
    {
        $httpClient = new FakeHttpClient();
        $queue = new WebHookQueue(new WebHookHandler($httpClient));

        $queue->push(new WebHooks($this->createWebHook(delay: 0.001)));
        EventLoop::run();

        $this->assertCount(1, $httpClient->calls);
    }

    public function testPushContinuesSendingAfterException(): void
    {
        $logger = new FakeLogger();
        $httpClient = new FakeHttpClient(exception: new \RuntimeException('connection refused'));
        $queue = new WebHookQueue(new WebHookHandler($httpClient, $logger));

        $queue->push(new WebHooks(
            $this->createWebHook(url: 'https://hook1.example.com'),
            $this->createWebHook(url: 'https://hook2.example.com'),
        ));
        EventLoop::run();

        $this->assertCount(2, $logger->getMessages('error'));
    }
}
