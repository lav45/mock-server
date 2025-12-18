<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Watcher;

use Amp;
use Amp\DeferredCancellation;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use FastRoute\Dispatcher;
use Lav45\MockServer\DispatcherFactory;
use Lav45\MockServer\FileStorage;
use Lav45\MockServer\RequestFactoryInterface;
use Lav45\MockServer\Watcher;
use Lav45\Watcher\Listener;
use Lav45\Watcher\Watcher as FileWatcher;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

use function Amp\File\createDirectory;
use function Amp\File\deleteDirectory;
use function Amp\File\deleteFile;
use function Amp\File\isDirectory;
use function Amp\File\move;
use function Amp\File\write;

final class WatcherTest extends TestCase
{
    private Logger $logger;

    private string $watchDir;

    private RequestHandler $requestHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->watchDir = \sys_get_temp_dir() . '/mock_' . \uniqid('', false);

        createDirectory($this->watchDir);
    }

    private function initWatcher(): Watcher
    {
        $this->logger = new Logger();

        $this->requestHandler = new class implements RequestHandler {
            public function handleRequest(Request $request): Response
            {
                return new Response();
            }
        };

        $requestFactory = new readonly class ($this->requestHandler) implements RequestFactoryInterface {
            public function __construct(
                private RequestHandler $requestHandler,
            ) {}

            public function create(array $mock): RequestHandler
            {
                return $this->requestHandler;
            }
        };

        $dispatcherFactory = new DispatcherFactory(
            requestFactory: $requestFactory,
            logger: $this->logger,
            options: [
                'dispatcher' => GroupCountBased::class,
            ],
        );

        $fileStorage = new FileStorage(
            watchDir: $this->watchDir,
            logger: $this->logger,
        );

        return new Watcher\Watcher(
            $dispatcherFactory,
            $this->watchDir,
            $fileStorage,
            $this->logger,
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeDirectory($this->watchDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (isDirectory($dir) === false) {
            return;
        }
        $items = \array_diff(\scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = "{$dir}/{$item}";
            if (isDirectory($path)) {
                $this->removeDirectory($path);
            } else {
                deleteFile($path);
            }
        }
        deleteDirectory($dir);
    }

    private function createMockFile(string $filename, string $content): void
    {
        if (\strpos($filename, '/', 1) !== false) {
            $dir = $this->watchDir . \dirname($filename);
            if (isDirectory($dir) === false) {
                \mkdir($dir, recursive: true);
            }
        }
        $path = $this->watchDir . $filename;
        write($path, $content);
    }

    public function testInitLoadsValidMockFilesAndInitializesDispatcher(): void
    {
        $this->createMockFile('/mock1.json', \json_encode([
            [
                'request' => [
                    'url' => '/test1',
                    'method' => 'GET',
                ],
                'response' => [
                    'text' => 'Test 1',
                ],
            ],
        ]));

        $this->createMockFile('/subdir/subdir/mock2.json', \json_encode([
            [
                'request' => [
                    'url' => '/test2',
                    'method' => 'POST',
                ],
                'response' => [
                    'text' => 'Test 2',
                ],
            ],
        ]));

        $this->createMockFile('/ignored.txt', 'some text');
        $this->createMockFile('/.ignored.json', \json_encode([]));
        $this->createMockFile('/__ignored_dir/in_ignored_dir.json', \json_encode([]));
        $this->createMockFile('/invalid.json', 'this is not json');

        $watcher = $this->initWatcher();

        /** @var GroupCountBased $dispatcher */
        $dispatcher = $watcher->getDispatcher();

        $this->assertCount(2, $dispatcher->staticRouteMap);

        $expectedMocksDataForDispatcher = [
            'GET' => [
                '/test1' => $this->requestHandler,
            ],
            'POST' => [
                '/test2' => $this->requestHandler,
            ],
        ];
        $this->assertEquals($expectedMocksDataForDispatcher, $dispatcher->staticRouteMap);
    }

    public function testInitHandlesBadRouteExceptionDuringDispatcherCreation(): void
    {
        $this->createMockFile('/bad_route.json', \json_encode([
            [
                'request' => [
                    'method' => ['INVALID METHOD'],
                ],
            ],
        ]));

        $watcher = $this->initWatcher();

        /** @var GroupCountBased $dispatcher */
        $dispatcher = $watcher->getDispatcher();

        $this->assertCount(0, $dispatcher->staticRouteMap);

        $this->assertEquals('error', $this->logger->level);
        $this->assertStringContainsString("InvalidArgumentException: 'INVALID METHOD'", $this->logger->message);
    }

    public function testOnSetFileUpdatesMocksAndSetsFlashMocks(): void
    {
        $watcher = $this->initWatcher();

        createDirectory($this->watchDir . '/subir');

        $cancellation = new DeferredCancellation();
        $listener = Amp\async(
            static fn() => $watcher->run(
                new FileWatcher(new Listener()),
                0.01,
                $cancellation,
            ),
        );

        /** @var GroupCountBased $dispatcher */
        $dispatcher = $watcher->getDispatcher();
        $this->assertCount(0, $dispatcher->staticRouteMap);

        // ======= Create =======
        $filePath = '/new_mock.json';
        $newMockData = [['request' => ['url' => '/new'], 'response' => ['text' => 'New']]];
        $this->createMockFile($filePath, \json_encode($newMockData));

        Amp\delay(0.02);

        $this->assertEquals('debug', $this->logger->level);
        $this->assertStringContainsString($this->watchDir . $filePath, $this->logger->message);

        /** @var GroupCountBased $dispatcher */
        $dispatcher = $watcher->getDispatcher();
        $this->assertCount(1, $dispatcher->staticRouteMap);

        $expectedMocksDataForDispatcher = [
            'GET' => [
                '/new' => $this->requestHandler,
            ],
        ];
        $this->assertEquals($expectedMocksDataForDispatcher, $dispatcher->staticRouteMap);

        // ======= Update =======
        $this->logger->reset();

        $newMockData = [['request' => ['url' => '/renew'], 'response' => ['text' => 'New']]];
        write($this->watchDir . $filePath, \json_encode($newMockData));

        Amp\delay(0.02);

        $this->assertEquals('debug', $this->logger->level);
        $this->assertStringContainsString($this->watchDir . $filePath, $this->logger->message);

        /** @var GroupCountBased $dispatcher */
        $dispatcher = $watcher->getDispatcher();
        $this->assertCount(1, $dispatcher->staticRouteMap);

        $expectedMocksDataForDispatcher = [
            'GET' => [
                '/renew' => $this->requestHandler,
            ],
        ];
        $this->assertEquals($expectedMocksDataForDispatcher, $dispatcher->staticRouteMap);

        // ======= Move =======
        $this->logger->reset();

        $newFilePath = '/test_move.json';
        move($this->watchDir . $filePath, $this->watchDir . $newFilePath);

        Amp\delay(0.02);

        $this->assertEquals('debug', $this->logger->level);
        $this->assertStringContainsString($this->watchDir . $newFilePath, $this->logger->message);

        /** @var GroupCountBased $dispatcher */
        $dispatcher = $watcher->getDispatcher();
        $this->assertCount(1, $dispatcher->staticRouteMap);

        $expectedMocksDataForDispatcher = [
            'GET' => [
                '/renew' => $this->requestHandler,
            ],
        ];
        $this->assertEquals($expectedMocksDataForDispatcher, $dispatcher->staticRouteMap);
        $filePath = $newFilePath;

        // ======= Delete =======
        $this->logger->reset();

        deleteFile($this->watchDir . $filePath);

        Amp\delay(0.02);

        $this->assertEquals('debug', $this->logger->level);
        $this->assertStringContainsString($this->watchDir . $filePath, $this->logger->message);

        /** @var GroupCountBased $dispatcher */
        $dispatcher = $watcher->getDispatcher();
        $this->assertCount(0, $dispatcher->staticRouteMap);

        // ======= Create invalid json file =======
        $this->logger->reset();

        $this->createMockFile('/invalid_on_set.json', 'not json {');

        Amp\delay(0.02);

        /** @var GroupCountBased $dispatcher */
        $dispatcher = $watcher->getDispatcher();
        $this->assertCount(0, $dispatcher->staticRouteMap);

        $this->assertEquals('error', $this->logger->level);
        $this->assertStringContainsString('Syntax error', $this->logger->message);

        // ======= End =======
        $cancellation->cancel();
        $listener->await();
    }
}

final class GroupCountBased extends Dispatcher\GroupCountBased
{
    public $staticRouteMap;

    public $variableRouteData;
}

final class Logger extends AbstractLogger
{
    public string|null $level = null;

    public string|null $message = null;

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->level = $level;
        $this->message = (string)$message;
    }

    public function reset(): void
    {
        $this->level = null;
        $this->message = null;
    }
}
