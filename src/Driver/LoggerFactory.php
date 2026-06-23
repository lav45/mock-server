<?php declare(strict_types=1);

namespace Lav45\MockServer\Driver;

use Amp\ByteStream;
use Amp\Cluster\Cluster;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;

final readonly class LoggerFactory
{
    public function __construct(
        private string     $name,
        private int|string $logLevel,
    ) {}

    public function create(): LoggerInterface
    {
        $name = $this->name . '-' . (Cluster::getContextId() ?? \getmypid());
        $handler = Cluster::isWorker()
            ? Cluster::createLogHandler($this->logLevel) // @codeCoverageIgnore
            : $this->createStreamHandler();

        return new Logger($name)->pushHandler($handler);
    }

    private function createStreamHandler(): HandlerInterface
    {
        $handler = new StreamHandler(ByteStream\getStdout());
        $handler->setLevel($this->logLevel);
        $handler->pushProcessor(new PsrLogMessageProcessor());
        $handler->setFormatter(new ConsoleFormatter(
            format: "[%datetime%]\t%level_name%\t%message%\t%context%\n",
            dateFormat: 'd.m.Y H:i:s.v',
            allowInlineLineBreaks: true,
            ignoreEmptyContextAndExtra: true,
        ));
        return $handler;
    }
}
