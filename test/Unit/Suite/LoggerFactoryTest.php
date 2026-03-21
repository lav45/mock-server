<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite;

use Lav45\MockServer\LoggerFactory;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

final class LoggerFactoryTest extends TestCase
{
    public function testCreateLogger(): void
    {
        $loggerName = 'test_logger';
        $logLevel = Level::Debug->value;

        $logger = new LoggerFactory($loggerName, $logLevel)->create();

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertSame($loggerName, $logger->getName());

        $handlers = $logger->getHandlers();
        $this->assertCount(1, $handlers);

        $handler = $handlers[0];
        $this->assertInstanceOf(AbstractHandler::class, $handler);

        $this->assertSame($logLevel, $handler->getLevel()->value);

        $formatter = $handler->getFormatter();
        $this->assertInstanceOf(LineFormatter::class, $formatter);

        $logLevel = 'debug';
        $logger = new LoggerFactory($loggerName, $logLevel)->create();

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertSame($loggerName, $logger->getName());

        $handlers = $logger->getHandlers();
        $this->assertCount(1, $handlers);

        $handler = $handlers[0];
        $this->assertInstanceOf(AbstractHandler::class, $handler);

        $this->assertSame(Level::fromName($logLevel), $handler->getLevel());
    }
}
