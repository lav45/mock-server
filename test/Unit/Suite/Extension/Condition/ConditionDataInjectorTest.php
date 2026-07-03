<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Extension\Condition;

use Lav45\MockServer\Domain\ValueObject\Value;
use Lav45\MockServer\Extension\Condition\ConditionDataInjector;
use PHPUnit\Framework\TestCase;

final class ConditionDataInjectorTest extends TestCase
{
    public function testEmptyInjectorLeavesDataUnchanged(): void
    {
        $data = ['response' => ['status' => 200], 'webhooks' => [['url' => 'https://a']]];

        $this->assertSame($data, new ConditionDataInjector()->replace($data));
    }

    public function testReplacesResponse(): void
    {
        $injector = new ConditionDataInjector(response: ['status' => 402]);

        $result = $injector->replace(['response' => ['status' => 200]]);

        $this->assertSame(['status' => 402], $result['response']);
    }

    public function testReplacesWebhooks(): void
    {
        $injector = new ConditionDataInjector(webhooks: [['url' => 'https://new']]);

        $result = $injector->replace(['webhooks' => [['url' => 'https://old']]]);

        $this->assertSame([['url' => 'https://new']], $result['webhooks']);
    }

    public function testEmptyWebhooksArraySuppressesWebhooks(): void
    {
        $injector = new ConditionDataInjector(response: ['status' => 200], webhooks: []);

        $result = $injector->replace(['webhooks' => [['url' => 'https://old']]]);

        $this->assertSame([], $result['webhooks']);
    }

    public function testUndefinedWebhooksKeepsExistingWebhooks(): void
    {
        $injector = new ConditionDataInjector(response: ['status' => 200], webhooks: Value::Undefined);

        $result = $injector->replace(['webhooks' => [['url' => 'https://old']]]);

        $this->assertSame([['url' => 'https://old']], $result['webhooks']);
    }
}
